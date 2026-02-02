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
                        ->whereDate('p.created_at', '=', $date);
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
                ->whereDate('profit_presentases.created_at', $date)
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
                    'profit_presentases.created_at'
                )
                ->where('sevices.id_teknisi', '=', $this_user->id)
                ->whereDate('profit_presentases.created_at', $date)
                ->orderBy('profit_presentases.created_at', 'desc')
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


    // Method untuk mendapatkan riwayat komisi teknisi + profit toko
    // Bisa difilter berdasarkan tanggal dan teknisi (jika admin/owner)
    public function getTechnicianCommissionHistory(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        $user = Auth::user();
        
        // Ambil jabatan dari user_details jika tidak ada di table users
        // Pastikan load userDetail agar tidak query berulang
        $jabatan = $user->jabatan;
        if (is_null($jabatan)) {
            $userDetail = DB::table('user_details')->where('kode_user', $user->id)->first();
            $jabatan = $userDetail ? $userDetail->jabatan : null;
        }

        // Default range: Awal bulan ini sampai Akhir bulan ini
        // Gunakan Carbon::parse untuk memastikan format tanggal benar jika dikirim string
        try {
            $startDate = $request->has('start_date') 
                ? Carbon::parse($request->start_date)->startOfDay()->format('Y-m-d')
                : Carbon::now()->startOfMonth()->format('Y-m-d');
                
            $endDate = $request->has('end_date') 
                ? Carbon::parse($request->end_date)->endOfDay()->format('Y-m-d')
                : Carbon::now()->endOfMonth()->format('Y-m-d');
        } catch (\Exception $e) {
            // Fallback jika format tanggal invalid
            $startDate = Carbon::now()->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::now()->endOfMonth()->format('Y-m-d');
        }

        // Filter teknisi: 
        // - Jika Admin/Owner, bisa pilih teknisi tertentu via parameter 'technician_id'.
        // - Jika Teknisi, paksa filter ke ID mereka sendiri.
        $targetTechnicianId = null;
        
        // FIXED: Pass user object yang mungkin belum punya properti jabatan, 
        // tapi method getOwnerCode akan cari sendiri di user_details jika perlu.
        // Namun kita sudah punya $jabatan variable sekarang.
        $kode_owner = $this->getOwnerCode($user); 

        if ($jabatan == '1') {
            // Admin/Owner
            $reqTechId = $request->get('technician_id');
            // Pastikan tidak empty string atau 'null' string
            if (!empty($reqTechId) && $reqTechId !== 'null') {
                $targetTechnicianId = $reqTechId;
            }
        } else {
            // Teknisi / Staff lain
            $targetTechnicianId = $user->id;
        }

        try {
            // Query Dasar - Mulai dari Profit
            $query = DB::table('profit_presentases')
                ->join('sevices', 'profit_presentases.kode_service', '=', 'sevices.id')
                ->leftJoin('user_details', 'profit_presentases.kode_user', '=', 'user_details.kode_user') // Gunakan Left Join agar data tidak hilang jika user terhapus
                ->select(
                    'profit_presentases.id as commission_id',
                    'profit_presentases.created_at as tgl_profit', // Gunakan created_at sebagai acuan waktu
                    'sevices.kode_service',
                    'sevices.nama_pelanggan',
                    'sevices.type_unit',
                    'sevices.status_services',
                    'sevices.total_biaya',
                    'sevices.harga_sp as modal_part', 
                    'profit_presentases.profit as technician_commission',
                    'profit_presentases.profit_toko as store_profit',
                    'user_details.fullname as technician_name',
                    'user_details.kode_user as technician_id'
                )
                // Filter Tanggal berdasarkan created_at profit
                ->whereDate('profit_presentases.created_at', '>=', $startDate)
                ->whereDate('profit_presentases.created_at', '<=', $endDate);

            // Terapkan filter berdasarkan Role
            if ($jabatan == '1') {
                // Owner melihat data berdasarkan kode_owner di service
                $query->where('sevices.kode_owner', $kode_owner);

                // Jika Owner memilih teknisi spesifik
                if ($targetTechnicianId) {
                    $query->where('profit_presentases.kode_user', $targetTechnicianId);
                }
            } else {
                // Teknisi hanya melihat data sendiri
                $query->where('profit_presentases.kode_user', $user->id);
            }

            $results = $query->orderBy('profit_presentases.created_at', 'desc')->get();

            // Calculate Summary
            $summary = [
                'total_services' => $results->count(),
                'total_technician_commission' => (float) $results->sum('technician_commission'),
                'total_store_profit' => (float) $results->sum('store_profit'),
                'total_revenue' => (float) $results->sum('total_biaya'),
                'total_part_cost' => (float) $results->sum('modal_part'),
                'period' => [
                    'start' => $startDate,
                    'end' => $endDate
                ]
            ];
            
            // Grouping by Technician
            $technicianBreakdown = [];
            if ($jabatan == '1' && !$targetTechnicianId) {
                 $technicianBreakdown = $results->groupBy(function($item) {
                     return $item->technician_name ?? 'Unknown'; // Handle jika technician_name null
                 })->map(function ($items, $name) {
                    return [
                        'technician_name' => $name,
                        'total_services' => $items->count(),
                        'total_commission' => (float) $items->sum('technician_commission'),
                        'total_store_profit' => (float) $items->sum('store_profit'),
                    ];
                })->values();
            }

            return response()->json([
                'success' => true,
                'debug_info' => [
                    'user_id' => $user->id,
                    'role' => $jabatan,
                    'kode_owner_used' => $kode_owner,
                    'date_filter' => [$startDate, $endDate],
                    'technician_filter' => $targetTechnicianId ?? 'ALL',
                    'query_count' => $results->count()
                ],
                'summary' => $summary,
                'technician_breakdown' => $technicianBreakdown,
                'data' => $results
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat komisi: ' . $e->getMessage()
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
