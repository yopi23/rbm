<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BebanOperasional;
use App\Models\UserDetail;
use App\Models\Penarikan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccumulatedFundsController extends Controller
{
    public function index()
    {
        $page = "Dana Terkumpul";

        // 1. Sinking Funds (Dana Beban Operasional)
        $sinkingFunds = BebanOperasional::where('is_active', true)
            ->where('kode_owner', auth()->user()->id_upline ?? auth()->user()->id)
            ->get();
        
        $totalSinkingFund = $sinkingFunds->sum('current_balance');

        // 2. Technician Commissions (Saldo Teknisi & Karyawan Lain)
        // Ambil semua user yang memiliki saldo (employees)
        $technicians = UserDetail::where('id_upline', auth()->user()->id_upline ?? auth()->user()->id)
            ->whereIn('jabatan', ['2', '3']) // Teknisi (3) dan Kasir (2)
            ->where('saldo', '>', 0)
            ->get();
            
        $totalTechnicianBalance = $technicians->sum('saldo');

        // 3. Pending Withdrawals (Dana yang akan keluar tapi belum dicairkan dari fisik)
        // Ini sudah mengurangi saldo teknisi, tapi belum mengurangi Kas Fisik (Masih dipegang)
        $pendingWithdrawals = Penarikan::where('kode_owner', auth()->user()->id_upline ?? auth()->user()->id)
            ->where('status_penarikan', '0')
            ->sum('jumlah_penarikan');

        // Total Accumulated Funds (Liabilitas / Dana Mengendap)
        // Saldo User + Pending Withdrawal (karena belum cash out) + Sinking Fund
        $totalAccumulated = $totalSinkingFund + $totalTechnicianBalance + $pendingWithdrawals;

        $content = view('admin.page.financial.accumulated_funds', compact(
            'page', 
            'sinkingFunds', 
            'totalSinkingFund', 
            'technicians', 
            'totalTechnicianBalance',
            'pendingWithdrawals',
            'totalAccumulated'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }
}
