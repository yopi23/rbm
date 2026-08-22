<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\KpiSetting;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\SalarySetting;
use App\Models\EmployeeMonthlyReport;
use App\Models\Attendance;
use App\Models\Violation;
use App\Models\Sevices as modelServices;
use App\Models\ProfitPresentase;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class KpiSettingApiController extends Controller
{
    /**
     * Helper untuk mendapatkan kode owner pengguna saat ini
     */
    private function getOwnerCode()
    {
        $user = auth()->user();
        if (!$user) return '0';

        $detail = UserDetail::where('kode_user', $user->id)->first();
        if (!$detail) return (string) $user->id;

        // Jika jabatan 1 (owner) atau 0 (admin utama), kode owner adalah dirinya sendiri
        if ($detail->jabatan == '1' || $detail->jabatan == '0') {
            return (string) $user->id;
        }

        // Jika karyawan (kasir/teknisi), ambil id_upline
        return (string) ($detail->id_upline ?? $user->id);
    }

    /**
     * Get KPI Settings untuk Owner / Cabang
     */
    public function index(Request $request)
    {
        try {
            $ownerCode = $this->getOwnerCode();
            $setting = KpiSetting::getEffectiveSettings($ownerCode);

            // Hitung estimasi laba toko bulan ini untuk simulasi bonus pool
            $startOfMonth = Carbon::now()->startOfMonth();
            $endOfMonth = Carbon::now()->endOfMonth();

            $takenServiceIds = modelServices::whereIn('status_services', ['Diambil'])
                ->whereBetween('updated_at', [$startOfMonth, $endOfMonth])
                ->pluck('id');

            $currentMonthShopProfit = (float) ProfitPresentase::whereIn('kode_service', $takenServiceIds)->sum('profit_toko');
            $estimatedPoolBonus = ($currentMonthShopProfit * (float) $setting->pool_percentage) / 100;

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan KPI berhasil diambil',
                'data' => [
                    'setting' => $setting,
                    'current_shop_profit' => $currentMonthShopProfit,
                    'estimated_pool_bonus' => $estimatedPoolBonus,
                    'default_tier_rules' => KpiSetting::getDefaultTierRules(),
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get KPI settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat pengaturan KPI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store / Update KPI Settings
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();

            // Hanya Owner (1) atau Superadmin (0) yang berhak mengubah pengaturan
            if (!$detail || !in_array($detail->jabatan, ['0', '1', 0, 1])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya Owner yang dapat mengubah pengaturan KPI.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'weight_productivity' => 'required|numeric|min:0|max:100',
                'weight_quality' => 'required|numeric|min:0|max:100',
                'weight_rework' => 'required|numeric|min:0|max:100',
                'weight_discipline' => 'required|numeric|min:0|max:100',
                'weight_sop' => 'required|numeric|min:0|max:100',
                'bonus_scheme' => 'required|in:pool,tiered,hybrid',
                'pool_percentage' => 'required|numeric|min:0|max:100',
                'min_kpi_bonus' => 'required|numeric|min:0|max:100',
                'tier_rules' => 'nullable|array',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi data gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Validasi total bobot harus 100%
            $totalWeight = (float) $request->weight_productivity +
                           (float) $request->weight_quality +
                           (float) $request->weight_rework +
                           (float) $request->weight_discipline +
                           (float) $request->weight_sop;

            if (abs($totalWeight - 100.0) > 0.1) {
                return response()->json([
                    'success' => false,
                    'message' => "Total bobot KPI harus pas 100%. Saat ini totalnya: {$totalWeight}%"
                ], 422);
            }

            $ownerCode = $this->getOwnerCode();

            $setting = KpiSetting::updateOrCreate(
                ['kode_owner' => $ownerCode],
                [
                    'weight_productivity' => (float) $request->weight_productivity,
                    'weight_quality' => (float) $request->weight_quality,
                    'weight_rework' => (float) $request->weight_rework,
                    'weight_discipline' => (float) $request->weight_discipline,
                    'weight_sop' => (float) $request->weight_sop,
                    'bonus_scheme' => $request->bonus_scheme,
                    'pool_percentage' => (float) $request->pool_percentage,
                    'min_kpi_bonus' => (float) $request->min_kpi_bonus,
                    'tier_rules' => $request->tier_rules ?? KpiSetting::getDefaultTierRules(),
                    'is_active' => true,
                    'updated_by' => $user->id,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Pengaturan KPI & Bonus berhasil disimpan',
                'data' => $setting
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error store KPI settings: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan pengaturan KPI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Summary Kinerja KPI Teknisi (Realtime / Bulan Berjalan)
     */
    public function getEmployeeKpiSummary(Request $request, $userId = null)
    {
        try {
            $user = auth()->user();
            $targetUserId = $userId ?? $request->query('user_id', $user->id);

            $targetUser = User::find($targetUserId);
            if (!$targetUser) {
                return response()->json(['success' => false, 'message' => 'Karyawan tidak ditemukan'], 404);
            }

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            $ownerCode = $this->getOwnerCode();
            $kpiSetting = KpiSetting::getEffectiveSettings($ownerCode);
            $salarySetting = SalarySetting::where('user_id', $targetUserId)->first();

            // 1. Produktivitas
            $allServices = modelServices::where('id_teknisi', $targetUserId)
                ->where(function ($query) use ($startDate, $endDate) {
                    $query->where(function ($q) use ($startDate, $endDate) {
                        $q->where('status_services', 'Diambil')
                          ->whereBetween('updated_at', [$startDate, $endDate]);
                    })->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('status_services', 'Selesai')
                          ->whereBetween('tgl_service', [$startDate, $endDate]);
                    });
                })
                ->get();

            $totalServiceUnits = $allServices->count();
            $monthlyTarget = $salarySetting ? (int) $salarySetting->monthly_target : 0;
            
            if ($monthlyTarget > 0) {
                $scoreProductivity = min(120.0, ($totalServiceUnits / $monthlyTarget) * 100);
            } else {
                $scoreProductivity = $totalServiceUnits > 0 ? 100.0 : 0.0;
            }

            // 2. Kualitas (Komplain & Klaim)
            $complaintsCount = Violation::where('user_id', $targetUserId)
                ->where('type', 'komplain')
                ->where('status', 'processed')
                ->whereBetween('violation_date', [$startDate, $endDate])
                ->count();

            $scoreQuality = max(0.0, 100.0 - ($complaintsCount * 15.0));

            // 3. Rework Rate
            $claimsFromOwnWork = modelServices::query()
                ->from('sevices as claims')
                ->join('sevices as originals', 'claims.claimed_from_service_id', '=', 'originals.id')
                ->where('originals.id_teknisi', $targetUserId)
                ->whereNotNull('claims.claimed_from_service_id')
                ->whereBetween('claims.created_at', [$startDate, $endDate])
                ->count();

            $reworkRate = $totalServiceUnits > 0 ? ($claimsFromOwnWork / $totalServiceUnits) * 100 : 0;
            $scoreRework = max(0.0, 100.0 - ($reworkRate * 5.0));

            // 4. Disiplin (Absensi)
            $attendances = Attendance::where('user_id', $targetUserId)
                ->whereBetween('attendance_date', [$startDate, $endDate])
                ->get();

            $totalLateMinutes = $attendances->sum('late_minutes');
            $totalPresentDays = $attendances->where('status', 'hadir')->count();
            $totalAbsentDays = $attendances->where('status', 'alpha')->count();
            $totalLeaveDays = $attendances->whereIn('status', ['izin'])->count();

            $scoreDiscipline = max(0.0, 100.0 - ($totalLateMinutes / 10.0) - ($totalAbsentDays * 25.0) - ($totalLeaveDays * 5.0));

            // 5. SOP & Administrasi
            $sopCompleteCount = 0;
            foreach ($allServices as $srv) {
                $checklist = $srv->sop_checklist;
                if (!empty($checklist) && is_array($checklist)) {
                    $penerimaan = $checklist['penerimaan'] ?? false;
                    $pengerjaan = $checklist['pengerjaan'] ?? false;
                    $selesai = $checklist['selesai'] ?? false;
                    if ($penerimaan && $pengerjaan && $selesai) {
                        $sopCompleteCount++;
                    }
                }
            }

            $scoreSop = $totalServiceUnits > 0 ? ($sopCompleteCount / $totalServiceUnits) * 100 : 0.0;

            if ($totalServiceUnits == 0) {
                $scoreQuality = 0.0;
                $scoreRework = 0.0;
            }

            // Rasio pembuktian produktivitas (Volume scaling)
            // Agar teknisi yang baru mengerjakan 1 dari 100 unit tidak langsung mendapat skor 60%
            $volumeRatio = ($monthlyTarget > 0) ? min(1.0, $totalServiceUnits / $monthlyTarget) : ($totalServiceUnits > 0 ? 1.0 : 0.0);

            $effectiveQuality = $scoreQuality * $volumeRatio;
            $effectiveRework = $scoreRework * $volumeRatio;
            $effectiveSop = $scoreSop * $volumeRatio;
            $effectiveDiscipline = $scoreDiscipline; // Disiplin dinilai dari kehadiran di toko

            // Total KPI Terbobot yang Adil & Proporsional
            $finalKpiScore = round(
                ($scoreProductivity * (float) $kpiSetting->weight_productivity / 100) +
                ($effectiveQuality * (float) $kpiSetting->weight_quality / 100) +
                ($effectiveRework * (float) $kpiSetting->weight_rework / 100) +
                ($effectiveDiscipline * (float) $kpiSetting->weight_discipline / 100) +
                ($effectiveSop * (float) $kpiSetting->weight_sop / 100),
                2
            );

            // Prediksi Kategori Kinerja
            $categoryLabel = 'Perlu Pembinaan';
            $categoryColor = 'red';
            if ($totalServiceUnits == 0 && $scoreDiscipline > 0) {
                $categoryLabel = 'Belum Ada Servis';
                $categoryColor = 'gray';
            } elseif ($finalKpiScore >= 100) {
                $categoryLabel = 'Istimewa (Exceed)';
                $categoryColor = 'purple';
            } elseif ($finalKpiScore >= 95) {
                $categoryLabel = 'Sangat Baik (A)';
                $categoryColor = 'green';
            } elseif ($finalKpiScore >= 90) {
                $categoryLabel = 'Baik (B+)';
                $categoryColor = 'blue';
            } elseif ($finalKpiScore >= 80) {
                $categoryLabel = 'Cukup (B)';
                $categoryColor = 'teal';
            } elseif ($finalKpiScore >= 70) {
                $categoryLabel = 'Standar Minimal (C)';
                $categoryColor = 'orange';
            }

            // Estimasi Komisi & Bonus (Syarat: Wajib capai minimal 70% target unit & minimal KPI)
            $serviceIds = $allServices->pluck('id');
            $totalCommission = (float) ProfitPresentase::whereIn('kode_service', $serviceIds)
                ->where('kode_user', $targetUserId)->sum('profit');

            $isEligibleBonus = ($scoreProductivity >= 70.0) && ($finalKpiScore >= (float) $kpiSetting->min_kpi_bonus);
            $estimatedBonus = $isEligibleBonus ? $kpiSetting->calculateTierBonus($finalKpiScore) : 0.0;

            // Gaji Pokok
            $basicSalary = 0;
            if ($salarySetting && $salarySetting->compensation_type === 'fixed') {
                $basicSalary = (float) $salarySetting->basic_salary * $totalPresentDays;
            }

            // Denda Pelanggaran
            $violations = Violation::where('user_id', $targetUserId)
                ->where('status', 'processed')
                ->whereBetween('violation_date', [$startDate, $endDate])
                ->get();
            $totalPenalties = (float) $violations->sum('applied_penalty_amount');

            $estimatedTakeHomePay = $basicSalary + $totalCommission + $estimatedBonus - $totalPenalties;

            return response()->json([
                'success' => true,
                'data' => [
                    'employee' => [
                        'id' => $targetUser->id,
                        'name' => $targetUser->name,
                        'role' => $targetUser->userDetail?->jabatan == 3 ? 'Teknisi' : 'Kasir/Karyawan',
                    ],
                    'period' => [
                        'month' => (int) $month,
                        'year' => (int) $year,
                        'month_name' => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
                    ],
                    'kpi' => [
                        'final_score' => $finalKpiScore,
                        'category_label' => $categoryLabel,
                        'category_color' => $categoryColor,
                        'is_eligible_bonus' => $finalKpiScore >= (float) $kpiSetting->min_kpi_bonus,
                        'weights' => [
                            'productivity' => (float) $kpiSetting->weight_productivity,
                            'quality' => (float) $kpiSetting->weight_quality,
                            'rework' => (float) $kpiSetting->weight_rework,
                            'discipline' => (float) $kpiSetting->weight_discipline,
                            'sop' => (float) $kpiSetting->weight_sop,
                        ],
                        'breakdown' => [
                            'productivity' => [
                                'score' => round($scoreProductivity, 1),
                                'actual' => $totalServiceUnits,
                                'target' => $monthlyTarget,
                                'unit' => 'unit',
                            ],
                            'quality' => [
                                'score' => round($scoreQuality, 1),
                                'complaints' => $complaintsCount,
                                'unit' => 'komplain',
                            ],
                            'rework' => [
                                'score' => round($scoreRework, 1),
                                'rework_count' => $claimsFromOwnWork,
                                'rework_rate' => round($reworkRate, 1),
                                'unit' => '%',
                            ],
                            'discipline' => [
                                'score' => round($scoreDiscipline, 1),
                                'late_minutes' => $totalLateMinutes,
                                'absent_days' => $totalAbsentDays,
                                'present_days' => $totalPresentDays,
                                'unit' => 'menit telat',
                            ],
                            'sop' => [
                                'score' => round($scoreSop, 1),
                                'complete_units' => $sopCompleteCount,
                                'total_units' => $totalServiceUnits,
                                'unit' => 'unit lengkap',
                            ],
                        ],
                    ],
                    'payroll_preview' => [
                        'basic_salary' => $basicSalary,
                        'commission' => $totalCommission,
                        'estimated_bonus' => $estimatedBonus,
                        'penalties' => $totalPenalties,
                        'take_home_pay' => $estimatedTakeHomePay,
                        'compensation_type' => $salarySetting?->compensation_type ?? 'fixed',
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get employee KPI summary: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan KPI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Leaderboard / Ranking KPI Seluruh Teknisi untuk Owner
     */
    public function getOwnerKpiLeaderboard(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();

            if (!$detail || !in_array($detail->jabatan, ['0', '1', 0, 1])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya Owner yang dapat mengakses leaderboard.'
                ], 403);
            }

            $month = $request->query('month', now()->month);
            $year = $request->query('year', now()->year);

            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->where('user_details.id_upline', $user->id)
                ->where('user_details.jabatan', 3) // Hanya Teknisi
                ->where('user_details.status_user', '1')
                ->select(['users.id', 'users.name', 'user_details.jabatan'])
                ->get();

            $leaderboard = [];

            foreach ($employees as $emp) {
                $summaryResponse = $this->getEmployeeKpiSummary($request, $emp->id);
                $summaryData = json_decode($summaryResponse->getContent(), true);

                if ($summaryData['success'] && isset($summaryData['data'])) {
                    $data = $summaryData['data'];
                    $leaderboard[] = [
                        'user_id' => $emp->id,
                        'name' => $emp->name,
                        'final_kpi_score' => $data['kpi']['final_score'],
                        'category_label' => $data['kpi']['category_label'],
                        'category_color' => $data['kpi']['category_color'],
                        'breakdown' => $data['kpi']['breakdown'],
                        'estimated_bonus' => $data['payroll_preview']['estimated_bonus'],
                        'total_commission' => $data['payroll_preview']['commission'],
                        'total_units' => $data['kpi']['breakdown']['productivity']['actual'],
                        'rework_rate' => $data['kpi']['breakdown']['rework']['rework_rate'],
                    ];
                }
            }

            // Urutkan dari KPI tertinggi ke terendah
            usort($leaderboard, function ($a, $b) {
                return $b['final_kpi_score'] <=> $a['final_kpi_score'];
            });

            // Berikan nomor peringkat (Rank)
            foreach ($leaderboard as $index => &$item) {
                $item['rank'] = $index + 1;
            }

            return response()->json([
                'success' => true,
                'message' => 'Leaderboard KPI berhasil dimuat',
                'data' => [
                    'period' => [
                        'month' => (int) $month,
                        'year' => (int) $year,
                        'month_name' => Carbon::create($year, $month, 1)->translatedFormat('F Y'),
                    ],
                    'total_technicians' => count($leaderboard),
                    'leaderboard' => $leaderboard,
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get KPI leaderboard: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memuat leaderboard KPI: ' . $e->getMessage()
            ], 500);
        }
    }
}
