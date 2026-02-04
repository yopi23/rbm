<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BebanOperasional;
use App\Models\UserDetail;
use App\Models\Penarikan;
use Illuminate\Http\Request;

class AccumulatedFundsApiController extends Controller
{
    public function index()
    {
        try {
            $user = $this->getThisUser();
            $kode_owner = $user->id_upline ?? $user->id;

            // 1. Sinking Funds (Dana Beban Operasional)
            $sinkingFunds = BebanOperasional::where('is_active', true)
                ->where('kode_owner', $kode_owner)
                ->get();
            
            $totalSinkingFund = $sinkingFunds->sum('current_balance');

            // 2. Technician & Cashier Balance (Saldo Teknisi & Karyawan Lain)
            $technicians = UserDetail::where('id_upline', $kode_owner)
                ->whereIn('jabatan', ['2', '3']) // Teknisi (3) dan Kasir (2)
                ->where('saldo', '>', 0)
                ->get();
            
            $totalTechnicianBalance = $technicians->sum('saldo');

            // 3. Pending Withdrawals
            $pendingWithdrawals = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '0')
                ->sum('jumlah_penarikan');

            // Total Accumulated Funds (Liabilitas)
            $totalAccumulated = $totalSinkingFund + $totalTechnicianBalance + $pendingWithdrawals;

            return response()->json([
                'success' => true,
                'data' => [
                    'total_accumulated' => $totalAccumulated,
                    'total_sinking_fund' => $totalSinkingFund,
                    'total_technician_balance' => $totalTechnicianBalance,
                    'pending_withdrawals' => $pendingWithdrawals,
                    'details' => [
                        'sinking_funds' => $sinkingFunds,
                        'technicians' => $technicians
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve accumulated funds data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
