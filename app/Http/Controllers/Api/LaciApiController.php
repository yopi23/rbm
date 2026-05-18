<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\HasOwnerAccess;

class LaciApiController extends Controller
{
    use HasOwnerAccess;

    public function getLaciBreakdown(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
            ]);

            $tgl_awal = Carbon::parse($request->tgl_awal)->format('Y-m-d');
            $tgl_akhir = Carbon::parse($request->tgl_akhir)->format('Y-m-d');

            $kode_owner = $this->getThisUser()->id_upline;

            Log::info('Laci Breakdown Request', [
                'user_id' => auth()->user()->id ?? null,
                'period' => $tgl_awal . ' to ' . $tgl_akhir
            ]);

            // Ambil semua kategori laci
            $kategoriLaci = DB::table('kategori_lacis')
                ->where('kode_owner', $kode_owner)
                ->select('id', 'name_laci')
                ->get();

            $breakdownPerLaci = [];
            $totalUangRealSemua = 0;

            foreach ($kategoriLaci as $laci) {
                $laciId = $laci->id;
                $laciName = $laci->name_laci;

                // Hitung total masuk dan keluar dari history_laci dalam periode
                $historyData = DB::table('history_laci')
                    ->where('kode_owner', $kode_owner)
                    ->where('id_kategori', $laciId)
                    ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                    ->selectRaw('
                        SUM(masuk) as total_masuk,
                        SUM(keluar) as total_keluar,
                        COUNT(*) as total_transaksi
                    ')
                    ->first();

                $totalMasuk = $historyData->total_masuk ?? 0;
                $totalKeluar = $historyData->total_keluar ?? 0;
                $totalTransaksi = $historyData->total_transaksi ?? 0;
                $saldoLaci = $totalMasuk - $totalKeluar;

                // Ambil 5 transaksi terakhir untuk detail
                $transaksiTerakhir = DB::table('history_laci')
                    ->where('kode_owner', $kode_owner)
                    ->where('id_kategori', $laciId)
                    ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->select('masuk', 'keluar', 'keterangan', 'created_at')
                    ->get();

                $breakdownPerLaci[] = [
                    'laci_id' => $laciId,
                    'nama_laci' => $laciName,
                    'total_masuk' => (float) $totalMasuk,
                    'total_keluar' => (float) $totalKeluar,
                    'saldo_laci' => (float) $saldoLaci,
                    'total_transaksi' => (int) $totalTransaksi,
                    'transaksi_terakhir' => $transaksiTerakhir->map(function($item) {
                        return [
                            'masuk' => (float) $item->masuk,
                            'keluar' => (float) $item->keluar,
                            'keterangan' => $item->keterangan,
                            'tanggal' => $item->created_at,
                            'tipe' => $item->masuk > 0 ? 'masuk' : 'keluar'
                        ];
                    }),
                    'status' => $saldoLaci >= 0 ? 'positif' : 'negatif'
                ];

                $totalUangRealSemua += $saldoLaci;
            }

            // Sort berdasarkan saldo terbesar
            usort($breakdownPerLaci, function($a, $b) {
                return $b['saldo_laci'] <=> $a['saldo_laci'];
            });

            // Hitung persentase
            foreach ($breakdownPerLaci as &$laci) {
                $laci['persentase_dari_total'] = $totalUangRealSemua != 0 ?
                    round(($laci['saldo_laci'] / $totalUangRealSemua) * 100, 2) : 0;
            }

            // Summary
            $summary = [
                'total_laci' => count($kategoriLaci),
                'laci_dengan_saldo_positif' => count(array_filter($breakdownPerLaci, function($laci) {
                    return $laci['saldo_laci'] > 0;
                })),
                'laci_dengan_saldo_negatif' => count(array_filter($breakdownPerLaci, function($laci) {
                    return $laci['saldo_laci'] < 0;
                })),
                'total_uang_real_semua_laci' => (float) $totalUangRealSemua,
                'laci_terbesar' => $breakdownPerLaci ? $breakdownPerLaci[0] : null,
                'laci_terkecil' => $breakdownPerLaci ? end($breakdownPerLaci) : null,
                'total_masuk_semua' => array_sum(array_column($breakdownPerLaci, 'total_masuk')),
                'total_keluar_semua' => array_sum(array_column($breakdownPerLaci, 'total_keluar')),
                'total_transaksi_semua' => array_sum(array_column($breakdownPerLaci, 'total_transaksi'))
            ];

            return response()->json([
                'success' => true,
                'message' => 'Breakdown uang real per laci berhasil diambil',
                'data' => [
                    'breakdown_per_laci' => $breakdownPerLaci,
                    'summary' => $summary,
                    'periode' => [
                        'awal' => $tgl_awal,
                        'akhir' => $tgl_akhir
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Laci Breakdown Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function getLaciHistory(Request $request, $laciId)
    {
        try {
            $request->validate([
                'tgl_awal' => 'sometimes|date',
                'tgl_akhir' => 'sometimes|date|after_or_equal:tgl_awal',
                'limit' => 'sometimes|integer|min:1|max:100',
                'page' => 'sometimes|integer|min:1',
                'tipe' => 'sometimes|in:masuk,keluar,semua'
            ]);

            $kode_owner = $this->getThisUser()->id_upline;
            $limit = $request->get('limit', 20);
            $page = $request->get('page', 1);
            $offset = ($page - 1) * $limit;
            $tipe = $request->get('tipe', 'semua');

            // Validate laci exists and belongs to owner
            $laci = DB::table('kategori_lacis')
                ->where('id', $laciId)
                ->where('kode_owner', $kode_owner)
                ->first();

            if (!$laci) {
                return response()->json([
                    'success' => false,
                    'message' => 'Laci tidak ditemukan'
                ], 404);
            }

            // Build query
            $query = DB::table('history_laci')
                ->where('kode_owner', $kode_owner)
                ->where('id_kategori', $laciId);

            // Add date filter if provided
            if ($request->has('tgl_awal') && $request->has('tgl_akhir')) {
                $query->whereBetween('created_at', [
                    $request->tgl_awal . ' 00:00:00',
                    $request->tgl_akhir . ' 23:59:59'
                ]);
            }

            // Add type filter
            if ($tipe === 'masuk') {
                $query->where('masuk', '>', 0);
            } elseif ($tipe === 'keluar') {
                $query->where('keluar', '>', 0);
            }

            // Get total count for pagination
            $totalCount = $query->count();

            // Get paginated data
            $history = $query
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get()
                ->map(function($item) {
                    return [
                        'id' => $item->id ?? null,
                        'masuk' => (float) $item->masuk,
                        'keluar' => (float) $item->keluar,
                        'keterangan' => $item->keterangan,
                        'tanggal' => $item->created_at,
                        'tipe' => $item->masuk > 0 ? 'masuk' : 'keluar',
                        'jumlah' => $item->masuk > 0 ? (float) $item->masuk : (float) $item->keluar
                    ];
                });

            // Calculate summary for this laci in the filtered period
            $summaryQuery = DB::table('history_laci')
                ->where('kode_owner', $kode_owner)
                ->where('id_kategori', $laciId);

            if ($request->has('tgl_awal') && $request->has('tgl_akhir')) {
                $summaryQuery->whereBetween('created_at', [
                    $request->tgl_awal . ' 00:00:00',
                    $request->tgl_akhir . ' 23:59:59'
                ]);
            }

            $summary = $summaryQuery
                ->selectRaw('
                    SUM(masuk) as total_masuk,
                    SUM(keluar) as total_keluar,
                    COUNT(*) as total_transaksi
                ')
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'History laci berhasil diambil',
                'data' => [
                    'laci_info' => [
                        'id' => $laci->id,
                        'nama_laci' => $laci->name_laci
                    ],
                    'history' => $history,
                    'summary' => [
                        'total_masuk' => (float) ($summary->total_masuk ?? 0),
                        'total_keluar' => (float) ($summary->total_keluar ?? 0),
                        'saldo' => (float) (($summary->total_masuk ?? 0) - ($summary->total_keluar ?? 0)),
                        'total_transaksi' => (int) ($summary->total_transaksi ?? 0)
                    ],
                    'pagination' => [
                        'current_page' => $page,
                        'total_items' => $totalCount,
                        'per_page' => $limit,
                        'total_pages' => ceil($totalCount / $limit),
                        'has_more' => ($offset + $limit) < $totalCount
                    ],
                    'filters' => [
                        'tipe' => $tipe,
                        'tgl_awal' => $request->get('tgl_awal'),
                        'tgl_akhir' => $request->get('tgl_akhir')
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Laci History Error', [
                'user_id' => auth()->user()->id ?? null,
                'laci_id' => $laciId,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function exportLaciBreakdown(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'format' => 'sometimes|in:csv,excel',
                'include_transactions' => 'sometimes|boolean'
            ]);

            $format = $request->get('format', 'csv');
            $includeTransactions = $request->get('include_transactions', false);

            $breakdownRequest = new Request([
                'tgl_awal' => $request->tgl_awal,
                'tgl_akhir' => $request->tgl_akhir
            ]);

            $breakdownResponse = $this->getLaciBreakdown($breakdownRequest);
            $breakdownData = json_decode($breakdownResponse->getContent(), true);

            if (!$breakdownData['success']) {
                throw new \Exception('Gagal mengambil data breakdown');
            }

            return response()->json([
                'success' => true,
                'message' => 'Data siap untuk export',
                'data' => [
                    'export_format' => $format,
                    'include_transactions' => $includeTransactions,
                    'breakdown_data' => $breakdownData['data'],
                    'export_note' => 'Implementasi export akan menggunakan Laravel Excel package'
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
