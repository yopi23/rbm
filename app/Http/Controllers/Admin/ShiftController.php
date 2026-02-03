<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\UserDetail;
use App\Models\Penjualan;
use App\Models\Sevices;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ShiftController extends Controller
{
    private function getOwnerId()
    {
        $user = Auth::user();
        if(!$user) return null;
        if($user->userDetail->jabatan == '1') return $user->id;
        return $user->userDetail->id_upline;
    }

    public function index()
    {
        $page = 'Riwayat Shift';
        $ownerId = $this->getOwnerId();
        $shifts = Shift::where('kode_owner', $ownerId)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('admin.page.shift.index', compact('shifts', 'page'));
    }

    public function create()
    {
        $page = 'Buka Shift';
        $activeShift = Shift::getActiveShift(Auth::id());
        if ($activeShift) {
            $startTime = Carbon::parse($activeShift->start_time);
            $warningMsg = 'Toko sudah memiliki shift yang aktif.';
            
            // Jika shift sudah berlangsung lebih dari 12 jam (kemungkinan lupa tutup)
            if ($startTime->diffInHours(now()) > 12) {
                $warningMsg = 'Shift sebelumnya (' . $startTime->format('d M H:i') . ') belum ditutup. Silakan tutup shift tersebut dahulu sebelum membuka shift baru.';
            }

            return redirect()->route('shift.show', $activeShift->id)->with('warning', $warningMsg);
        }

        return view('admin.page.shift.create', compact('page'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'modal_awal' => 'required|numeric|min:0',
        ]);

        $activeShift = Shift::getActiveShift(Auth::id());
        if ($activeShift) {
            return redirect()->route('shift.show', $activeShift->id)->with('warning', 'Toko sudah memiliki shift yang aktif.');
        }

        Shift::create([
            'kode_owner' => $this->getOwnerId(),
            'user_id' => Auth::id(),
            'start_time' => now(),
            'modal_awal' => $request->modal_awal,
            'status' => 'open',
        ]);

        return redirect()->route('dashboard')->with('success', 'Shift berhasil dibuka.');
    }

    public function show($id)
    {
        $shift = Shift::with(['user', 'penjualans', 'services', 'pengeluaranTokos', 'kasPerusahaan'])->findOrFail($id);
        
        if($shift->kode_owner != $this->getOwnerId()) abort(403);

        $sparepartReport = [];
        $cashIn = 0;
        $cashOut = 0;
        $expectedCash = 0;

        if ($shift->status == 'closed' && $shift->report_data) {
            // Use snapshot data
            $report = is_string($shift->report_data) ? json_decode($shift->report_data, true) : $shift->report_data;
            $cashIn = $report['cash_in'] ?? 0;
            $cashOut = $report['cash_out'] ?? 0;
            $expectedCash = $report['expected_cash'] ?? 0;
            $sparepartReport = $report['sparepart_analysis'] ?? [];
        } else {
            // Live calculation
            // Calculate Cash Flow based on KasPerusahaan (Ledger) linked to this Shift
            $cashIn = $shift->kasPerusahaan()->sum('debit');
            $cashOut = $shift->kasPerusahaan()->sum('kredit');
            $expectedCash = $shift->modal_awal + $cashIn - $cashOut;
            $sparepartReport = $this->getSparepartAnalysis($shift);
        }

        // Detailed Transactions (Always fetch live for details)
        // Correct relations: detailSparepart for Penjualan, partToko for Sevices
        $penjualans = $shift->penjualans()->where('status_penjualan', '1')->with('detailSparepart.sparepart')->get();
        $services = $shift->services()->where('status_services', 'Diambil')->with(['partToko.sparepart', 'partLuar'])->get();
        $pengeluaranTokos = $shift->pengeluaranTokos;
        $pengeluaranOperasionals = $shift->pengeluaranOperasionals;

        $page = 'Detail Shift #' . $shift->id;
        return view('admin.page.shift.show', compact('shift', 'expectedCash', 'cashIn', 'cashOut', 'penjualans', 'services', 'pengeluaranTokos', 'pengeluaranOperasionals', 'sparepartReport', 'page'));
    }

    public function close($id)
    {
        return $this->edit($id);
    }

    public function edit($id)
    {
        $shift = Shift::findOrFail($id);
        if($shift->status == 'closed') return redirect()->route('shift.show', $shift->id);
        
        $cashIn = $shift->kasPerusahaan()->sum('debit');
        $cashOut = $shift->kasPerusahaan()->sum('kredit');
        $expectedCash = $shift->modal_awal + $cashIn - $cashOut;
        
        $page = 'Tutup Shift';
        return view('admin.page.shift.close', compact('shift', 'expectedCash', 'page'));
    }

    public function update(Request $request, $id)
    {
        $shift = Shift::findOrFail($id);
        $request->validate([
            'saldo_akhir_aktual' => 'required|numeric|min:0',
        ]);

        $cashIn = $shift->kasPerusahaan()->sum('debit');
        $cashOut = $shift->kasPerusahaan()->sum('kredit');
        $expectedCash = $shift->modal_awal + $cashIn - $cashOut;

        // Generate Snapshot Report
        $sparepartReport = $this->getSparepartAnalysis($shift);
        
        $reportData = [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'expected_cash' => $expectedCash,
            'sparepart_analysis' => $sparepartReport,
            'closed_at' => now()->toDateTimeString(),
            'closed_by' => Auth::id()
        ];

        $shift->update([
            'end_time' => now(),
            'saldo_akhir_sistem' => $expectedCash,
            'saldo_akhir_aktual' => $request->saldo_akhir_aktual,
            'selisih' => $request->saldo_akhir_aktual - $expectedCash,
            'status' => 'closed',
            'note' => $request->input('note'),
            'report_data' => json_encode($reportData),
        ]);

        return redirect()->route('shift.show', $shift->id)->with('success', 'Shift berhasil ditutup.');
    }

    private function getSparepartAnalysis($shift)
    {
        $data = [];

        $startTime = $shift->start_time;
        $endTime = $shift->end_time ?? now();
        $userId = $shift->user_id;

        // Query StockHistory
        // Priority 1: Filter by shift_id (guarantees no mixing)
        // Priority 2: Filter by user and time (legacy/fallback for records without shift_id)
        $histories = StockHistory::with('sparepart')
            ->where(function($query) use ($shift, $userId, $startTime, $endTime) {
                $query->where('shift_id', $shift->id)
                      ->orWhere(function($q) use ($userId, $startTime, $endTime) {
                          $q->whereNull('shift_id')
                            ->where('user_input', $userId)
                            ->whereBetween('created_at', [$startTime, $endTime]);
                      });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($histories as $history) {
            $id = $history->sparepart_id;
            
            if (!$history->sparepart) continue;

            if (!isset($data[$id])) {
                $data[$id] = [
                    'nama' => $history->sparepart->nama_sparepart,
                    'used' => 0,
                    'stock_in' => 0,
                    'current_stock' => 0, 
                    'initial_stock_est' => 0 // This will be calculated based on the FIRST history record found
                ];

                // Set initial stock estimation based on the first record found in this period
                // Logic: If this is the first record, the stock BEFORE this change was the initial stock for this period.
                $data[$id]['initial_stock_est'] = $history->stock_before;
            }

            // Aggregate changes
            if ($history->quantity_change < 0) {
                $data[$id]['used'] += abs($history->quantity_change);
            } else {
                $data[$id]['stock_in'] += $history->quantity_change;
            }

            // Always update current_stock to the latest stock_after in this sequence
            $data[$id]['current_stock'] = $history->stock_after;
            
            // Recalculate remaining stock (sisa) to be consistent
            $data[$id]['sisa'] = $data[$id]['current_stock'];
        }

        return $data;
    }
}
