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
        return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
    }

    $this_user = Auth::user();
    $date = $request->get('date', Carbon::today()->format('Y-m-d'));

    try {
        $kode_owner = $this->getOwnerCode($this_user);

        // LANGKAH 1: Hitung rata-rata persentase 'beban' dari teknisi komisi
        $avgPercentage = DB::table('salary_settings')
            ->join('user_details', 'salary_settings.user_id', '=', 'user_details.kode_user')
            ->where('user_details.id_upline', $kode_owner)
            ->where('user_details.jabatan', 3)
            ->where('salary_settings.compensation_type', 'percentage')
            ->where('salary_settings.is_active', true)
            ->avg('max_percentage'); // Menggunakan max_percentage sebagai dasar

        // Default ke 0 jika tidak ada teknisi persentase sama sekali
        $avgShopLoadPercentage = $avgPercentage ?? 0;


        // LANGKAH 2: Gunakan rata-rata persentase dalam query utama
        $commissions = DB::table('users')
            ->join('user_details', 'users.id', '=', 'user_details.kode_user')
            ->join('salary_settings', 'users.id', '=', 'salary_settings.user_id')
            ->leftJoin('sevices', function($join) use ($kode_owner) {
                $join->on('users.id', '=', 'sevices.id_teknisi')
                     ->where('sevices.kode_owner', '=', $kode_owner);
            })
            ->leftJoin('profit_presentases as p', function ($join) use ($date) { // Alias untuk keringkasan
                $join->on('sevices.id', '=', 'p.kode_service')
                     ->whereDate('p.updated_at', '=', $date);
            })
            ->select(
                'user_details.fullname',
                'users.id as user_id',
                'salary_settings.compensation_type',

                // 1. Nilai untuk DITAMPILKAN (actual_commission) - Tidak berubah
                DB::raw("CASE
                    WHEN salary_settings.compensation_type = 'fixed' THEN 0
                    ELSE SUM((IFNULL(p.profit, 0) + IFNULL(p.profit_toko, 0)) * salary_settings.max_percentage / 100)
                END as actual_commission"),

                // 2. Nilai untuk DIBANDINGKAN (generated_profit) - DIUBAH TOTAL
                DB::raw("CASE
                    WHEN salary_settings.compensation_type = 'fixed'
                    THEN SUM(IFNULL(p.profit, 0) + IFNULL(p.profit_toko, 0)) * ({$avgShopLoadPercentage} / 100)
                    ELSE SUM((IFNULL(p.profit, 0) + IFNULL(p.profit_toko, 0)) * salary_settings.max_percentage / 100)
                END as generated_profit"),

                DB::raw('COUNT(DISTINCT p.id) as service_count')
            )
            ->where('salary_settings.is_active', true)
            ->where('user_details.id_upline', '=', $kode_owner)
            ->where('user_details.jabatan', 3)
            ->groupBy('user_details.fullname', 'users.id', 'salary_settings.compensation_type')
            ->orderBy('generated_profit', 'desc')
            ->get();

        $grand_total_commission = $commissions->sum('actual_commission');

        return response()->json([
            'success' => true,
            'data' => $commissions,
            'date' => $date,
            'total_technicians' => $commissions->count(),
            'grand_total_commission' => $grand_total_commission,
            'debug_avg_percentage' => $avgShopLoadPercentage // Opsional: untuk debugging di frontend
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
