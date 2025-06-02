<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CommissionController extends Controller
{
    public function getTodayCommissions(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $this_user = Auth::user();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        try {
            // FIXED: Ambil kode_owner yang benar
            $kode_owner = $this->getOwnerCode($this_user);

            $commissions = DB::table('profit_presentases')
                ->join('sevices', 'sevices.id', '=', 'profit_presentases.kode_service')
                ->join('user_details', 'sevices.id_teknisi', '=', 'user_details.kode_user')
                ->join('users', 'users.id', '=', 'user_details.kode_user')
                ->select(
                    'user_details.fullname',
                    'users.id as user_id',
                    DB::raw('SUM(profit_presentases.profit) as total_commission'),
                    DB::raw('COUNT(profit_presentases.id) as service_count'),
                    DB::raw('AVG(profit_presentases.profit) as avg_commission')
                )
                ->where('sevices.kode_owner', '=', $kode_owner) // FIXED: Gunakan kode_owner yang benar
                ->whereDate('profit_presentases.updated_at', $date)
                ->groupBy('user_details.fullname', 'users.id', 'user_details.kode_user')
                ->orderBy('total_commission', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $commissions,
                'date' => $date,
                'total_technicians' => $commissions->count(),
                'grand_total_commission' => $commissions->sum('total_commission')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data komisi: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getMyTodayCommission(Request $request)
    {
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        $this_user = Auth::user();
        $date = $request->get('date', Carbon::today()->format('Y-m-d'));

        try {
            $myCommission = DB::table('profit_presentases')
                ->join('sevices', 'sevices.id', '=', 'profit_presentases.kode_service')
                ->select(
                    DB::raw('SUM(profit_presentases.profit) as total_commission'),
                    DB::raw('COUNT(profit_presentases.id) as service_count'),
                    DB::raw('AVG(profit_presentases.profit) as avg_commission')
                )
                ->where('sevices.id_teknisi', '=', $this_user->id)
                ->whereDate('profit_presentases.updated_at', $date)
                ->first();

            $todayServices = DB::table('profit_presentases')
                ->join('sevices', 'sevices.id', '=', 'profit_presentases.kode_service')
                ->select(
                    'sevices.nama_pelanggan',
                    'sevices.type_unit',
                    'sevices.keterangan',
                    'profit_presentases.profit',
                    'sevices.total_biaya',
                    'sevices.status_services',
                    'profit_presentases.updated_at'
                )
                ->where('sevices.id_teknisi', '=', $this_user->id)
                ->whereDate('profit_presentases.updated_at', $date)
                ->orderBy('profit_presentases.updated_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'total_commission' => $myCommission->total_commission ?? 0,
                    'service_count' => $myCommission->service_count ?? 0,
                    'avg_commission' => $myCommission->avg_commission ?? 0,
                    'services' => $todayServices
                ],
                'date' => $date
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data komisi: ' . $e->getMessage()
            ], 500);
        }
    }

    // FIXED: Method untuk mendapatkan kode_owner yang benar
    private function getOwnerCode($user)
    {
        // Jika user adalah admin (jabatan = 1), kode_owner adalah id user tersebut
        if ($user->jabatan == '1') {
            return $user->id;
        }

        // Jika user adalah kasir atau teknisi, ambil id_upline dari user_details
        $userDetail = DB::table('user_details')
            ->where('kode_user', $user->id)
            ->first();

        // Jika ada user_details dengan id_upline, gunakan itu
        if ($userDetail && $userDetail->id_upline) {
            return $userDetail->id_upline;
        }

        // Fallback: gunakan id user sebagai kode_owner
        return $user->id;
    }

    // Method lama untuk backward compatibility
    public function getThisUser()
    {
        $user = Auth::user();

        // FIXED: Tambahkan properti id_upline
        $userDetail = DB::table('user_details')
            ->where('kode_user', $user->id)
            ->first();

        if ($userDetail) {
            $user->id_upline = $userDetail->id_upline;
        }

        return $user;
    }
}
