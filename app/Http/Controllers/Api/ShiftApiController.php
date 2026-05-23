<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StockHistory;
use App\Models\User;
use App\Models\UserDetail;
use App\Services\FCMService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShiftApiController extends Controller
{
    private function getOwnerId()
    {
        $user = Auth::user();
        if (!$user)
            return null;
        if ($user->userDetail && $user->userDetail->jabatan == '1')
            return $user->id;
        return $user->userDetail ? $user->userDetail->id_upline : null;
    }

    /**
     * Get Current Active Shift Status
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        $activeShift = Shift::getActiveShift($user->id);

        if (!$activeShift) {
            return response()->json([
                'success' => true,
                'status' => 'closed',
                'message' => 'No active shift found',
                'data' => null
            ]);
        }

        $shiftData = $this->formatShiftData($activeShift);
        $shiftData['shift_logs'] = \App\Models\ShiftLog::with('user')->where('shift_id', $activeShift->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'status' => 'open',
            'message' => 'Active shift found',
            'data' => $shiftData
        ]);
    }

    /**
     * Open a new shift
     */
    public function open(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modal_awal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $activeShift = Shift::getActiveShift($user->id);

        if ($activeShift) {
            $startTime = \Carbon\Carbon::parse($activeShift->start_time);
            $message = 'Store already has an active shift.';

            if ($startTime->diffInHours(now()) > 12) {
                $message = 'Shift sebelumnya (' . $startTime->format('d M H:i') . ') belum ditutup. Harap tutup shift tersebut dahulu.';
            }

            return response()->json([
                'success' => false,
                'message' => $message,
                'data' => $this->formatShiftData($activeShift)
            ], 400);
        }

        $shift = Shift::create([
            'kode_owner' => $this->getOwnerId(),
            'user_id' => $user->id,
            'start_time' => now(),
            'modal_awal' => $request->modal_awal,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift opened successfully',
            'data' => $this->formatShiftData($shift)
        ], 201);
    }

    /**
     * Close the active shift
     */
    public function close(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'saldo_akhir_aktual' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $shift = Shift::getActiveShift($user->id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found to close.',
            ], 404);
        }

        // Calculate financials
        // Formula: expectedCash = modal_awal + cashIn - cashOut
        // 
        // INCLUDE in cashIn/cashOut:
        // - Penjualan (Sales) → Uang masuk dari customer untuk produk
        // - Sevices (Services) → Uang masuk dari customer untuk service
        // - PemasukkanLain (Other Income) → Uang masuk lainnya yang langsung masuk ke laci
        // - PengeluaranToko (Store Expenses) → Uang keluar untuk kebutuhan toko
        // - PengeluaranOperasional (Operational Expenses) → Uang keluar untuk operasional
        // 
        // EXCLUDE from cashIn/cashOut (karena menggunakan "Kas Toko/Safe", bukan "Kas Laci/Drawer"):
        // - Pembelian (Purchases) → Tidak dari laci, dari kas toko/safe supplier
        // - Hutang (Dept Payment) → Pembayaran hutang, dari kas toko bukan laci
        //
        $excludedTypes = [
            'App\Models\Pembelian',
            'App\Models\Hutang',
            'App\Models\AlokasiLaba',
            'App\Models\DistribusiLaba'
        ];

        $cashIn = $shift->kasPerusahaan()
            ->where('is_cash', true)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('debit');

        $cashOut = $shift->kasPerusahaan()
            ->where('is_cash', true)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('kredit');

        // Transfer / Non-Tunai
        $transferIn = $shift->kasPerusahaan()
            ->where('is_cash', false)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('debit');

        $transferOut = $shift->kasPerusahaan()
            ->where('is_cash', false)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('kredit');

        $expectedCash = $shift->modal_awal + $cashIn - $cashOut;

        // Generate Snapshot Report
        $sparepartReport = $this->getSparepartAnalysis($shift);

        $reportData = [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'transfer_in' => $transferIn,
            'transfer_out' => $transferOut,
            'expected_cash' => $expectedCash,
            'sparepart_analysis' => $sparepartReport,
            'closed_at' => now()->toDateTimeString(),
            'closed_by' => $user->id
        ];

        $saldoAkhirAktual = $request->saldo_akhir_aktual;
        $selisih = $saldoAkhirAktual - $expectedCash;

        $shift->update([
            'end_time' => now(),
            'saldo_akhir_sistem' => $expectedCash,
            'saldo_akhir_aktual' => $saldoAkhirAktual,
            'selisih' => $selisih,
            'status' => 'closed',
            'note' => $request->input('note'),
            'report_data' => json_encode($reportData),
        ]);

        try {
            if ($shift->kode_owner) {
                $owner = User::find($shift->kode_owner);
                if ($owner && !empty($owner->fcm_token)) {
                    $userDetail = UserDetail::where('kode_user', $user->id)->first();
                    $employeeName = $userDetail ? $userDetail->fullname : 'Karyawan';

                    $expectedFmt = 'Rp ' . number_format($expectedCash, 0, ',', '.');
                    $actualFmt = 'Rp ' . number_format($saldoAkhirAktual, 0, ',', '.');
                    $selisihFmt = 'Rp ' . number_format($selisih, 0, ',', '.');

                    $statusText = $selisih == 0 ? "✅ Balance (Aman)" : ($selisih < 0 ? "⚠️ Minus (Kurang)" : "📈 Surplus (Lebih)");

                    $messageBody = "👤 Kasir: {$employeeName}\n" .
                        "⏱️ Waktu: " . now()->format('H:i') . "\n\n" .
                        "💰 Seharusnya: {$expectedFmt}\n" .
                        "💵 Uang Fisik laci: {$actualFmt}\n" .
                        "⚖️ Selisih: {$selisihFmt}\n" .
                        "Status: {$statusText}\n\n" .
                        "Laporan otomatis dari sistem POS.";

                    FCMService::sendNotification(
                        $owner->fcm_token,
                        'Laporan Tutup Shift 🔐',
                        $messageBody,
                    [
                        'type' => 'shift_closed',
                        'shift_id' => $shift->id
                    ]
                    );
                }
            }
        }
        catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send FCM notification for closed shift: " . $e->getMessage());
        }

        $mutasiDetails = $shift->kasPerusahaan()
            ->whereNotIn('sourceable_type', [
            'App\Models\Pembelian',
            'App\Models\Hutang',
            'App\Models\AlokasiLaba',
            'App\Models\DistribusiLaba'
        ])
            ->orderBy('tanggal', 'asc')
            ->get()
            ->map(function ($kas) {
            $sourceable = $kas->sourceable;
            $namaPelanggan = null;
            $keteranganTambahan = null;

            if ($sourceable) {
                $type = class_basename($kas->sourceable_type);
                if ($type === 'Pengambilan') {
                    $servicesRecords = \App\Models\Sevices::where('kode_pengambilan', $sourceable->id)->get();
                    $namaPelangganAsli = $servicesRecords->pluck('nama_pelanggan')->filter()->unique()->implode(', ');
                    
                    if (!empty($namaPelangganAsli)) {
                        if (strtolower($namaPelangganAsli) !== strtolower($sourceable->nama_pengambilan)) {
                            $namaPelanggan = $namaPelangganAsli . ' (Pengambil: ' . $sourceable->nama_pengambilan . ')';
                        } else {
                            $namaPelanggan = $namaPelangganAsli;
                        }
                    } else {
                        $namaPelanggan = $sourceable->nama_pengambilan;
                    }

                    $services = $servicesRecords->map(function($svc) {
                        return ($svc->type_unit ? $svc->type_unit . ' (' . ($svc->keterangan ?? '-') . ')' : ($svc->keterangan ?? '-'));
                    })->toArray();
                    $keteranganTambahan = implode(', ', $services);
                } elseif ($type === 'Sevices' || $type === 'Service') {
                    $namaPelanggan = $sourceable->nama_pelanggan;
                    $keteranganTambahan = ($sourceable->type_unit ? $sourceable->type_unit . ' (' . ($sourceable->keterangan ?? '-') . ')' : ($sourceable->keterangan ?? '-'));
                } elseif ($type === 'Penjualan') {
                    $namaPelanggan = $sourceable->nama_customer ?? 'Walk-in';
                    $keteranganTambahan = $sourceable->catatan_customer;
                } elseif ($type === 'PemasukkanLain' || $type === 'PengeluaranToko' || $type === 'PengeluaranOperasional') {
                    $namaPelanggan = '-';
                    $keteranganTambahan = $sourceable->keterangan ?? $sourceable->catatan ?? '-';
                } elseif ($type === 'Penarikan') {
                    $user = \App\Models\UserDetail::where('kode_user', $sourceable->kode_user)->first();
                    $namaPelanggan = $user ? $user->fullname : '-';
                    $keteranganTambahan = $sourceable->catatan_penarikan;
                }
            }

            return [
            'id' => $kas->id,
            'tanggal' => date('Y-m-d H:i:s', strtotime($kas->tanggal)),
            'deskripsi' => $kas->deskripsi,
            'debit' => $kas->debit,
            'kredit' => $kas->kredit,
            'is_cash' => $kas->is_cash,
            'type' => class_basename($kas->sourceable_type),
            'nama_pelanggan' => $namaPelanggan,
            'keterangan_tambahan' => $keteranganTambahan
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Shift closed successfully',
            'data' => [
                'shift' => $shift,
                'summary' => [
                    'modal_awal' => $shift->modal_awal,
                    'cash_in' => $cashIn,
                    'cash_out' => $cashOut,
                    'transfer_in' => $transferIn,
                    'transfer_out' => $transferOut,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $saldoAkhirAktual,
                    'difference' => $selisih,
                    'sparepart_analysis' => $sparepartReport,
                    'transactions' => $mutasiDetails
                ]
            ]
        ]);
    }

    /**
     * Helper to format shift data
     */
    private function formatShiftData($shift)
    {
        return [
            'id' => $shift->id,
            'user_id' => $shift->user_id,
            'start_time' => $shift->start_time,
            'modal_awal' => $shift->modal_awal,
            'status' => $shift->status,
            'created_at' => $shift->created_at,
        ];
    }

    /**
     * Helper for sparepart analysis (Copied from Admin\ShiftController)
     */
    private function getSparepartAnalysis($shift)
    {
        $data = [];

        $startTime = $shift->start_time;
        $endTime = $shift->end_time ?? now();
        $userId = $shift->user_id;

        $histories = StockHistory::with('sparepart')
            ->where(function ($query) use ($shift, $userId, $startTime, $endTime) {
            $query->where('shift_id', $shift->id)
                ->orWhere(function ($q) use ($userId, $startTime, $endTime) {
                $q->whereNull('shift_id')
                    ->where('user_input', $userId)
                    ->whereBetween('created_at', [$startTime, $endTime]);
            }
            );
        })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($histories as $history) {
            $id = $history->sparepart_id;

            if (!$history->sparepart)
                continue;

            if (!isset($data[$id])) {
                $data[$id] = [
                    'nama' => $history->sparepart->nama_sparepart,
                    'used' => 0,
                    'stock_in' => 0,
                    'current_stock' => 0,
                    'initial_stock_est' => 0
                ];
            }

            if ($history->quantity_change < 0) {
                $data[$id]['used'] += abs($history->quantity_change);
            }
            else {
                $data[$id]['stock_in'] += $history->quantity_change;
            }

            $data[$id]['current_stock'] = $history->stock_after;
        }

        foreach ($data as &$item) {
            $item['initial_stock_est'] = $item['current_stock'] + $item['used'] - $item['stock_in'];
            $item['sisa'] = $item['current_stock'];
        }

        return array_values($data); // Return array instead of object with IDs as keys
    }

    /**
     * Get shift history for owner
     */
    public function history(Request $request)
    {
        $ownerId = $this->getOwnerId();

        $shifts = Shift::with(['user.userDetail'])
            ->where('kode_owner', $ownerId)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return response()->json([
            'success' => true,
            'message' => 'Shift history fetched',
            'data' => $shifts
        ]);
    }

    /**
     * Get specific shift details including financial and stock report
     */
    public function show($id)
    {
        $shift = Shift::with([
            'user.userDetail',
            'penjualans' => function ($q) {
            $q->where('status_penjualan', '1')->with('detailSparepart.sparepart');
        },
            'services' => function ($q) {
            $q->where('status_services', 'Diambil')->with(['partToko.sparepart', 'partLuar']);
        },
            'pengeluaranTokos',
            'kasPerusahaan.sourceable'
        ])->findOrFail($id);

        // Tambahkan informasi pelanggan dan keterangan pada kas_perusahaan
        $shift->kasPerusahaan->each(function($kas) {
            $kas->setAttribute('nama_pelanggan', null);
            $kas->setAttribute('keterangan_tambahan', null);
            $kas->setAttribute('type', class_basename($kas->sourceable_type));

            if ($kas->sourceable) {
                $type = class_basename($kas->sourceable_type);
                if ($type === 'Pengambilan') {
                    $servicesRecords = \App\Models\Sevices::where('kode_pengambilan', $kas->sourceable->id)->get();
                    $namaPelangganAsli = $servicesRecords->pluck('nama_pelanggan')->filter()->unique()->implode(', ');
                    
                    if (!empty($namaPelangganAsli)) {
                        if (strtolower($namaPelangganAsli) !== strtolower($kas->sourceable->nama_pengambilan)) {
                            $kas->setAttribute('nama_pelanggan', $namaPelangganAsli . ' (Pengambil: ' . $kas->sourceable->nama_pengambilan . ')');
                        } else {
                            $kas->setAttribute('nama_pelanggan', $namaPelangganAsli);
                        }
                    } else {
                        $kas->setAttribute('nama_pelanggan', $kas->sourceable->nama_pengambilan);
                    }

                    $services = $servicesRecords->map(function($svc) {
                        return ($svc->type_unit ? $svc->type_unit . ' (' . ($svc->keterangan ?? '-') . ')' : ($svc->keterangan ?? '-'));
                    })->toArray();
                    $kas->setAttribute('keterangan_tambahan', implode(', ', $services));
                } elseif ($type === 'Sevices' || $type === 'Service') {
                    $kas->setAttribute('nama_pelanggan', $kas->sourceable->nama_pelanggan);
                    $kas->setAttribute('keterangan_tambahan', ($kas->sourceable->type_unit ? $kas->sourceable->type_unit . ' (' . ($kas->sourceable->keterangan ?? '-') . ')' : ($kas->sourceable->keterangan ?? '-')));
                } elseif ($type === 'Penjualan') {
                    $kas->setAttribute('nama_pelanggan', $kas->sourceable->nama_customer ?? 'Walk-in');
                    $kas->setAttribute('keterangan_tambahan', $kas->sourceable->catatan_customer);
                } elseif ($type === 'PemasukkanLain' || $type === 'PengeluaranToko' || $type === 'PengeluaranOperasional') {
                    $kas->setAttribute('nama_pelanggan', '-');
                    $kas->setAttribute('keterangan_tambahan', $kas->sourceable->keterangan ?? $kas->sourceable->catatan ?? '-');
                } elseif ($type === 'Penarikan') {
                    $user = \App\Models\UserDetail::where('kode_user', $kas->sourceable->kode_user)->first();
                    $kas->setAttribute('nama_pelanggan', $user ? $user->fullname : '-');
                    $kas->setAttribute('keterangan_tambahan', $kas->sourceable->catatan_penarikan);
                }
            }
        });

        if ($shift->kode_owner != $this->getOwnerId()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $excludedTypes = [
            'App\Models\Pembelian',
            'App\Models\Hutang',
            'App\Models\AlokasiLaba',
            'App\Models\DistribusiLaba'
        ];

        $baseQuery = clone $shift->kasPerusahaan();

        $cashIn = (clone $baseQuery)
            ->where('is_cash', true)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('debit') ?? 0;

        $cashOut = (clone $baseQuery)
            ->where('is_cash', true)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('kredit') ?? 0;

        // Transfer / Non-Tunai
        $transferIn = (clone $baseQuery)
            ->where('is_cash', false)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('debit') ?? 0;

        $transferOut = (clone $baseQuery)
            ->where('is_cash', false)
            ->whereNotIn('sourceable_type', $excludedTypes)
            ->sum('kredit') ?? 0;

        $sparepartReport = [];
        $reportDataRaw = null;

        if ($shift->status == 'closed' && $shift->report_data) {
            $reportDataRaw = is_string($shift->report_data) ? json_decode($shift->report_data, true) : $shift->report_data;
            $sparepartReport = $reportDataRaw['sparepart_analysis'] ?? [];
            // Override with snapshot if valid
            $cashIn = $reportDataRaw['cash_in'] ?? $cashIn;
            $cashOut = $reportDataRaw['cash_out'] ?? $cashOut;
            $transferIn = $reportDataRaw['transfer_in'] ?? $transferIn;
            $transferOut = $reportDataRaw['transfer_out'] ?? $transferOut;
        }
        else {
            $sparepartReport = $this->getSparepartAnalysis($shift);
        }

        $totalPendapatan = $cashIn + $transferIn;

        $shiftLogs = \App\Models\ShiftLog::with('user')->where('shift_id', $shift->id)->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => [
                'shift' => $shift,
                'summary' => [
                    'modal_awal' => $shift->modal_awal,
                    'cash_in' => $cashIn,
                    'cash_out' => $cashOut,
                    'transfer_in' => $transferIn,
                    'transfer_out' => $transferOut,
                    'total_pendapatan' => $totalPendapatan,
                    'expected_cash' => $shift->saldo_akhir_sistem ?? ($shift->modal_awal + $cashIn - $cashOut),
                    'actual_cash' => $shift->saldo_akhir_aktual,
                    'selisih' => $shift->selisih,
                ],
                'sparepart_analysis' => $sparepartReport,
                'report_snapshot' => $reportDataRaw,
                'shift_logs' => $shiftLogs
            ]
        ]);
    }
}
