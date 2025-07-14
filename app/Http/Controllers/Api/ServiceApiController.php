<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailCatatanService;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\Garansi;
use App\Models\PresentaseUser;
use App\Models\ProfitPresentase;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\SalarySetting;
use App\Models\SubKategoriSparepart;
use App\Models\KategoriSparepart;
use App\Services\WhatsAppService;
use Milon\Barcode\Facades\DNS1DFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use PDO;

class ServiceApiController extends Controller
{
    /**
     * Get completed services for today with caching
     */
    public function getCompletedToday(Request $request)
{
    try {
        $today = date('Y-m-d');

        $completedServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->where('status_services', 'Selesai')
            ->whereDate('sevices.updated_at', $today)
            ->join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select('sevices.*', 'users.name as teknisi')
            ->orderBy('sevices.updated_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data layanan yang selesai hari ini berhasil diambil.',
            'data' => $completedServices,
            'total_today' => $completedServices->count(),
            'date' => $today
        ], 200);
    } catch (\Exception $e) {
        Log::error("Get Completed Today Error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * Check service status with enhanced data
     */
    public function checkServiceStatus(Request $request, $serviceId)
    {
        try {
            $cacheKey = "service_status_{$serviceId}";

            $serviceData = Cache::remember($cacheKey, 5, function () use ($serviceId) {
                $service = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('sevices.id', $serviceId)
                    ->leftJoin('users', 'sevices.id_teknisi', '=', 'users.id')
                    ->select(
                        'sevices.id as service_id',
                        'sevices.kode_service',
                        'sevices.nama_pelanggan',
                        'sevices.type_unit',
                        'sevices.keterangan',
                        'sevices.status_services',
                        'sevices.total_biaya',
                        'sevices.dp',
                        'sevices.harga_sp',
                        'sevices.created_at',
                        'sevices.updated_at',
                        'users.name as teknisi_name',
                        'users.id as teknisi_id'
                    )
                    ->first();

                if (!$service) {
                    return null;
                }

                // Get additional data
                $hasWarranty = Garansi::where('kode_garansi', $service->kode_service)
                    ->where('type_garansi', 'service')
                    ->exists();

                $hasNotes = DetailCatatanService::where('kode_services', $serviceId)->exists();

                $partCount = [
                    'toko' => DetailPartServices::where('kode_services', $serviceId)->count(),
                    'luar' => DetailPartLuarService::where('kode_services', $serviceId)->count()
                ];

                return [
                    'service' => $service,
                    'has_warranty' => $hasWarranty,
                    'has_notes' => $hasNotes,
                    'part_count' => $partCount,
                    'is_completed' => $service->status_services === 'Selesai'
                ];
            });

            if (!$serviceData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service tidak ditemukan.',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Status service berhasil diambil.',
                'data' => $serviceData,
            ], 200);

        } catch (\Exception $e) {
            Log::error("Check Service Status Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get all completed services with pagination and filters
     */
    public function getCompletedservice(Request $request)
{
    try {
        $page = $request->get('page', 1);
        $limit = min($request->get('limit', 20), 50);
        $search = $request->get('search', '');
        $technician_id = $request->get('technician_id');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');

        $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->where('status_services', 'Selesai')
            ->join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select('sevices.*', 'users.name as teknisi');

        // Apply filters
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('sevices.nama_pelanggan', 'LIKE', "%$search%")
                  ->orWhere('sevices.type_unit', 'LIKE', "%$search%")
                  ->orWhere('sevices.kode_service', 'LIKE', "%$search%")
                  ->orWhere('sevices.keterangan', 'LIKE', "%$search%");
            });
        }

        if ($technician_id) {
            $query->where('sevices.id_teknisi', $technician_id);
        }

        if ($date_from) {
            $query->whereDate('sevices.updated_at', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('sevices.updated_at', '<=', $date_to);
        }

        $totalCount = $query->count();
        $services = $query->orderBy('sevices.updated_at', 'desc')
                         ->offset(($page - 1) * $limit)
                         ->limit($limit)
                         ->get();

        return response()->json([
            'success' => true,
            'message' => 'Data layanan selesai berhasil diambil.',
            'data' => $services,
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $limit,
                'total' => (int) $totalCount,
                'total_pages' => ceil($totalCount / $limit),
                'has_next_page' => $page < ceil($totalCount / $limit),
                'has_previous_page' => $page > 1,
            ],
            'filters' => [
                'search' => $search,
                'technician_id' => $technician_id,
                'date_from' => $date_from,
                'date_to' => $date_to
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error("Get Completed Services Error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        ], 500);
    }
}


    /**
     * Search all services with enhanced functionality
     */
    public function allservice(Request $request)
    {
        try {
            $kodeOwner = $this->getThisUser()->id_upline;
            $search = $request->input('search');
            $page = $request->get('page', 1);
            $limit = min($request->get('limit', 20), 50);
            $status = $request->get('status'); // Filter by status
            $year = $request->get('year', date('Y'));

            if (empty($search)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Silakan masukkan kata kunci pencarian.',
                    'data' => [],
                    'pagination' => null
                ], 200);
            }

            $cacheKey = "all_services_{$kodeOwner}_{$search}_{$page}_{$limit}_{$status}_{$year}";

            $result = Cache::remember($cacheKey, 180, function () use ($kodeOwner, $search, $page, $limit, $status, $year) {
                $query = modelServices::where('kode_owner', $kodeOwner)
                    ->where(function ($q) use ($search) {
                        $q->where('sevices.nama_pelanggan', 'LIKE', "%$search%")
                          ->orWhere('sevices.type_unit', 'LIKE', "%$search%")
                          ->orWhere('sevices.kode_service', $search)
                          ->orWhere('sevices.keterangan', 'LIKE', "%$search%");
                    })
                    ->whereYear('sevices.created_at', $year)
                    ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                    ->select('sevices.*', 'users.name as teknisi');

                if ($status) {
                    $query->where('sevices.status_services', $status);
                }

                $totalCount = $query->count();
                $services = $query->orderBy('sevices.created_at', 'desc')
                                 ->offset(($page - 1) * $limit)
                                 ->limit($limit)
                                 ->get();

                // Enhance with additional data
                $services = $services->map(function ($service) {
                    $service->has_warranty = Garansi::where('kode_garansi', $service->kode_service)
                        ->where('type_garansi', 'service')
                        ->exists();

                    $service->has_notes = DetailCatatanService::where('kode_services', $service->id)->exists();

                    $service->part_count = [
                        'toko' => DetailPartServices::where('kode_services', $service->id)->count(),
                        'luar' => DetailPartLuarService::where('kode_services', $service->id)->count()
                    ];

                    return $service;
                });

                return [
                    'data' => $services,
                    'pagination' => [
                        'current_page' => (int) $page,
                        'per_page' => (int) $limit,
                        'total' => (int) $totalCount,
                        'total_pages' => ceil($totalCount / $limit),
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Data layanan ditemukan.',
                'data' => $result['data'],
                'pagination' => $result['pagination'],
                'search_query' => $search
            ], 200);
        } catch (\Exception $e) {
            Log::error("All Service Search Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check service with enhanced warranty and part information
     */
    function cekService(Request $request)
    {
        try {
            $kodeService = $request->q;

            if (empty($kodeService)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode service harus diisi'
                ], 400);
            }

            $cacheKey = "cek_service_{$kodeService}";

            $serviceData = Cache::remember($cacheKey, 300, function () use ($kodeService) {
                $data = modelServices::where('kode_service', $kodeService)->first();

                if (!$data) {
                    return null;
                }

                $teknisi = $data->id_teknisi ? User::where('id', $data->id_teknisi)->value('name') : '-';

                $garansi = Garansi::where('kode_garansi', $kodeService)
                    ->where('type_garansi', 'service')
                    ->get()
                    ->map(function ($g) {
                        $g->is_expired = now()->gt($g->tgl_exp_garansi);
                        $g->days_remaining = now()->diffInDays($g->tgl_exp_garansi, false);
                        $g->status = $this->getWarrantyStatus($g->tgl_exp_garansi);
                        return $g;
                    });

                $detail = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->where('detail_part_services.kode_services', $data->id)
                    ->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);

                $detail_luar = DetailPartLuarService::where('kode_services', $data->id)->get();

                $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                    ->where('detail_catatan_services.kode_services', $data->id)
                    ->select('detail_catatan_services.*', 'users.name as user_name')
                    ->orderBy('detail_catatan_services.tgl_catatan_service', 'desc')
                    ->get();

                // Calculate totals
                $total_part_toko = $detail->sum(function ($item) {
                    return $item->detail_harga_part_service * $item->qty_part;
                });

                $total_part_luar = $detail_luar->sum(function ($item) {
                    return $item->harga_part * $item->qty_part;
                });

                return [
                    'service' => $data,
                    'teknisi' => $teknisi,
                    'garansi' => $garansi,
                    'detail_part_toko' => $detail,
                    'detail_part_luar' => $detail_luar,
                    'catatan' => $catatan,
                    'totals' => [
                        'part_toko' => $total_part_toko,
                        'part_luar' => $total_part_luar,
                        'total_parts' => $total_part_toko + $total_part_luar,
                        'profit' => $data->total_biaya - ($total_part_toko + $total_part_luar)
                    ]
                ];
            });

            if (!$serviceData) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service not found'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Service data retrieved successfully',
                'data' => $serviceData
            ]);

        } catch (\Exception $e) {
            Log::error("Cek Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service statistics for dashboard
     */
    public function getServiceStatistics(Request $request)
    {
        try {
            $dateFrom = $request->get('date_from', now()->subDays(30)->format('Y-m-d'));
            $dateTo = $request->get('date_to', now()->format('Y-m-d'));

            $cacheKey = "service_statistics_{$this->getThisUser()->id_upline}_{$dateFrom}_{$dateTo}";

            $statistics = Cache::remember($cacheKey, 600, function () use ($dateFrom, $dateTo) {
                $baseQuery = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->whereBetween('created_at', [$dateFrom . ' 00:00:00', $dateTo . ' 23:59:59']);

                // Basic statistics
                $totalServices = $baseQuery->count();
                $completedServices = $baseQuery->where('status_services', 'Selesai')->count();
                $pendingServices = $baseQuery->where('status_services', 'Antri')->count();
                $inProgressServices = $baseQuery->where('status_services', 'Proses')->count();

                // Revenue statistics
                $totalRevenue = $baseQuery->where('status_services', 'Selesai')->sum('total_biaya');
                $totalDP = $baseQuery->sum('dp');
                $averageServiceValue = $completedServices > 0 ? $totalRevenue / $completedServices : 0;

                // Daily trends
                $dailyCompletions = $baseQuery
                    ->where('status_services', 'Selesai')
                    ->selectRaw('DATE(updated_at) as date, COUNT(*) as count, SUM(total_biaya) as revenue')
                    ->groupBy('date')
                    ->orderBy('date')
                    ->get();

                // Top technicians
                $topTechnicians = $baseQuery
                    ->where('status_services', 'Selesai')
                    ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                    ->selectRaw('users.id, users.name, COUNT(*) as completed_count, SUM(sevices.total_biaya) as total_revenue')
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('completed_count')
                    ->limit(10)
                    ->get();

                // Device type analysis
                $deviceTypes = $baseQuery
                    ->where('status_services', 'Selesai')
                    ->selectRaw('type_unit, COUNT(*) as count, SUM(total_biaya) as revenue, AVG(total_biaya) as avg_revenue')
                    ->groupBy('type_unit')
                    ->orderByDesc('count')
                    ->limit(10)
                    ->get();

                // Commission summary
                $completedServiceIds = $baseQuery->where('status_services', 'Selesai')->pluck('id');
                $totalCommissions = ProfitPresentase::whereIn('kode_service', $completedServiceIds)->sum('profit');

                return [
                    'summary' => [
                        'total_services' => $totalServices,
                        'completed_services' => $completedServices,
                        'pending_services' => $pendingServices,
                        'in_progress_services' => $inProgressServices,
                        'completion_rate' => $totalServices > 0 ? round(($completedServices / $totalServices) * 100, 2) : 0,
                        'total_revenue' => $totalRevenue,
                        'total_dp' => $totalDP,
                        'remaining_payments' => $totalRevenue - $totalDP,
                        'average_service_value' => $averageServiceValue,
                        'total_commissions' => $totalCommissions
                    ],
                    'trends' => [
                        'daily_completions' => $dailyCompletions
                    ],
                    'analysis' => [
                        'top_technicians' => $topTechnicians,
                        'device_types' => $deviceTypes
                    ],
                    'period' => [
                        'from' => $dateFrom,
                        'to' => $dateTo,
                        'days' => Carbon::parse($dateFrom)->diffInDays(Carbon::parse($dateTo)) + 1
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error("Get Service Statistics Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service performance metrics
     */
    public function getServicePerformance(Request $request)
    {
        try {
            $period = $request->get('period', 'week'); // week, month, quarter, year
            $technicianId = $request->get('technician_id');

            $cacheKey = "service_performance_{$this->getThisUser()->id_upline}_{$period}_{$technicianId}";

            $performance = Cache::remember($cacheKey, 300, function () use ($period, $technicianId) {
                $dateRange = $this->getDateRangeForPeriod($period);

                $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->whereBetween('created_at', $dateRange);

                if ($technicianId) {
                    $query->where('id_teknisi', $technicianId);
                }

                // Service completion times
                $completionTimes = $query->where('status_services', 'Selesai')
                    ->selectRaw('TIMESTAMPDIFF(HOUR, created_at, updated_at) as completion_hours')
                    ->pluck('completion_hours')
                    ->filter(function ($time) {
                        return $time > 0; // Filter out negative or zero times
                    });

                $avgCompletionTime = $completionTimes->avg();
                $minCompletionTime = $completionTimes->min();
                $maxCompletionTime = $completionTimes->max();

                // Service value analysis
                $serviceValues = $query->where('status_services', 'Selesai')
                    ->pluck('total_biaya')
                    ->filter(function ($value) {
                        return $value > 0;
                    });

                $avgServiceValue = $serviceValues->avg();
                $totalServiceValue = $serviceValues->sum();

                // Customer satisfaction metrics (if you have ratings)
                $satisfactionData = $this->getCustomerSatisfactionData($query->pluck('id'));

                return [
                    'completion_metrics' => [
                        'average_completion_hours' => round($avgCompletionTime ?? 0, 2),
                        'min_completion_hours' => $minCompletionTime ?? 0,
                        'max_completion_hours' => $maxCompletionTime ?? 0,
                        'total_services_analyzed' => $completionTimes->count()
                    ],
                    'value_metrics' => [
                        'average_service_value' => round($avgServiceValue ?? 0, 2),
                        'total_service_value' => $totalServiceValue ?? 0,
                        'service_count' => $serviceValues->count()
                    ],
                    'satisfaction_metrics' => $satisfactionData,
                    'period' => $period,
                    'date_range' => $dateRange
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $performance
            ]);

        } catch (\Exception $e) {
            Log::error("Get Service Performance Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching performance data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get warranty status for completed services
     */
    public function getWarrantyStatistics(Request $request)
    {
        try {
            $cacheKey = "warranty_statistics_{$this->getThisUser()->id_upline}";

            $statistics = Cache::remember($cacheKey, 600, function () {
                $totalWarranties = Garansi::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('type_garansi', 'service')
                    ->count();

                $activeWarranties = Garansi::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('type_garansi', 'service')
                    ->where('tgl_exp_garansi', '>', now())
                    ->count();

                $expiredWarranties = Garansi::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('type_garansi', 'service')
                    ->where('tgl_exp_garansi', '<=', now())
                    ->count();

                $expiringSoon = Garansi::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('type_garansi', 'service')
                    ->whereBetween('tgl_exp_garansi', [now(), now()->addDays(30)])
                    ->count();

                // Recent warranty claims (if you track them)
                $recentClaims = $this->getRecentWarrantyClaims();

                return [
                    'total_warranties' => $totalWarranties,
                    'active_warranties' => $activeWarranties,
                    'expired_warranties' => $expiredWarranties,
                    'expiring_soon' => $expiringSoon,
                    'warranty_coverage_rate' => $this->getWarrantyCoverageRate(),
                    'recent_claims' => $recentClaims
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $statistics
            ]);

        } catch (\Exception $e) {
            Log::error("Get Warranty Statistics Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching warranty statistics',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clear service-related caches
     */
    public function clearServiceCaches(Request $request)
    {
        try {
            $cacheTypes = $request->get('types', ['all']); // all, completed, statistics, performance
            $cleared = [];

            if (in_array('all', $cacheTypes) || in_array('completed', $cacheTypes)) {
                Cache::forget("completed_today_{$this->getThisUser()->id_upline}_" . date('Y-m-d'));
                $cleared[] = 'completed_today';
            }

            if (in_array('all', $cacheTypes) || in_array('statistics', $cacheTypes)) {
                // Clear statistics cache pattern
                $this->clearCachePattern("service_statistics_{$this->getThisUser()->id_upline}_*");
                $cleared[] = 'statistics';
            }

            if (in_array('all', $cacheTypes) || in_array('performance', $cacheTypes)) {
                $this->clearCachePattern("service_performance_{$this->getThisUser()->id_upline}_*");
                $cleared[] = 'performance';
            }

            if (in_array('all', $cacheTypes)) {
                Cache::flush(); // Nuclear option
                $cleared = ['all_caches'];
            }

            return response()->json([
                'success' => true,
                'message' => 'Cache cleared successfully',
                'cleared_types' => $cleared
            ]);

        } catch (\Exception $e) {
            Log::error("Clear Service Caches Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error clearing caches',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ===================================================================
    // HELPER METHODS
    // ===================================================================

    /**
     * Get warranty status based on expiry date
     */
    private function getWarrantyStatus($expiryDate)
    {
        $now = now();
        $expiry = Carbon::parse($expiryDate);
        $diffInDays = $now->diffInDays($expiry, false);

        if ($diffInDays < 0) {
            return 'expired';
        } elseif ($diffInDays == 0) {
            return 'expires_today';
        } elseif ($diffInDays <= 7) {
            return 'expires_soon';
        } else {
            return 'active';
        }
    }

    /**
     * Get date range for different periods
     */
    private function getDateRangeForPeriod($period)
    {
        $now = now();

        switch ($period) {
            case 'week':
                return [$now->subWeek(), $now];
            case 'month':
                return [$now->subMonth(), $now];
            case 'quarter':
                return [$now->subQuarter(), $now];
            case 'year':
                return [$now->subYear(), $now];
            default:
                return [$now->subWeek(), $now];
        }
    }

    /**
     * Get customer satisfaction data (placeholder)
     */
    private function getCustomerSatisfactionData($serviceIds)
    {
        // This would be implemented if you have a customer feedback/rating system
        // For now, returning placeholder data
        return [
            'average_rating' => 4.2,
            'total_reviews' => 0,
            'satisfaction_percentage' => 85
        ];
    }

    /**
     * Get recent warranty claims (placeholder)
     */
    private function getRecentWarrantyClaims()
    {
        // This would be implemented if you track warranty claims
        return [
            'total_claims_this_month' => 0,
            'pending_claims' => 0,
            'resolved_claims' => 0
        ];
    }

    /**
     * Get warranty coverage rate
     */
    private function getWarrantyCoverageRate()
    {
        try {
            $totalCompletedServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                ->where('status_services', 'Selesai')
                ->count();

            $servicesWithWarranty = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                ->where('status_services', 'Selesai')
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                          ->from('garansis')
                          ->whereRaw('garansis.kode_garansi = sevices.kode_service')
                          ->where('garansis.type_garansi', 'service');
                })
                ->count();

            return $totalCompletedServices > 0 ?
                round(($servicesWithWarranty / $totalCompletedServices) * 100, 2) : 0;

        } catch (\Exception $e) {
            Log::error("Get Warranty Coverage Rate Error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clear cache pattern (implementation depends on cache driver)
     */
    private function clearCachePattern($pattern)
    {
        try {
            // This is a simplified implementation
            // In production, you might want to use Redis SCAN or similar
            Cache::flush();
        } catch (\Exception $e) {
            Log::error("Clear Cache Pattern Error: " . $e->getMessage());
        }
    }

    /**
     * Export service data to various formats
     */
    public function exportServiceData(Request $request)
    {
        try {
            $format = $request->get('format', 'json'); // json, csv, excel
            $type = $request->get('type', 'completed'); // completed, all, statistics
            $filters = $request->only([
                'date_from',
                'date_to',
                'technician_id',
                'status',
                'include_details'
            ]);

            $exportData = $this->prepareExportData($type, $filters);

            switch ($format) {
                case 'csv':
                    return $this->exportToCsv($exportData);
                case 'excel':
                    return $this->exportToExcel($exportData);
                default:
                    return response()->json([
                        'success' => true,
                        'data' => $exportData,
                        'export_info' => [
                            'generated_at' => now()->toISOString(),
                            'generated_by' => auth()->user()->name,
                            'format' => $format,
                            'type' => $type,
                            'filters_applied' => array_filter($filters)
                        ]
                    ]);
            }

        } catch (\Exception $e) {
            Log::error("Export Service Data Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Export failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Prepare data for export
     */
    private function prepareExportData($type, $filters)
    {
        $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select('sevices.*', 'users.name as teknisi');

        // Apply type filter
        if ($type === 'completed') {
            $query->where('status_services', 'Selesai');
        }

        // Apply additional filters
        if (!empty($filters['date_from'])) {
            $query->whereDate('sevices.updated_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('sevices.updated_at', '<=', $filters['date_to']);
        }

        if (!empty($filters['technician_id'])) {
            $query->where('sevices.id_teknisi', $filters['technician_id']);
        }

        if (!empty($filters['status'])) {
            $query->where('sevices.status_services', $filters['status']);
        }

        $services = $query->orderBy('sevices.updated_at', 'desc')->get();

        // Include detailed information if requested
        if ($filters['include_details'] === 'true') {
            $services = $services->map(function ($service) {
                $service->warranties = Garansi::where('kode_garansi', $service->kode_service)
                    ->where('type_garansi', 'service')
                    ->get();

                $service->parts_toko = DetailPartServices::where('kode_services', $service->id)
                    ->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->get(['spareparts.nama_sparepart', 'detail_part_services.qty_part', 'detail_part_services.detail_harga_part_service']);

                $service->parts_luar = DetailPartLuarService::where('kode_services', $service->id)
                    ->get(['nama_part', 'qty_part', 'harga_part']);

                $service->notes = DetailCatatanService::where('kode_services', $service->id)
                    ->join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                    ->get(['detail_catatan_services.catatan_service', 'users.name', 'detail_catatan_services.tgl_catatan_service']);

                return $service;
            });
        }

        return $services;
    }

    /**
     * Get service health metrics
     */
    public function getServiceHealth(Request $request)
    {
        try {
            $healthData = [
                'database_status' => $this->checkDatabaseHealth(),
                'cache_status' => $this->checkCacheHealth(),
                'api_performance' => $this->getApiPerformanceMetrics(),
                'data_integrity' => $this->checkDataIntegrity(),
                'system_resources' => $this->getSystemResourceUsage()
            ];

            $overallHealth = $this->calculateOverallHealth($healthData);

            return response()->json([
                'success' => true,
                'overall_health' => $overallHealth,
                'health_data' => $healthData,
                'timestamp' => now()->toISOString()
            ]);

        } catch (\Exception $e) {
            Log::error("Get Service Health Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Health check failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Check database health
     */
    private function checkDatabaseHealth()
    {
        try {
            $start = microtime(true);
            $count = modelServices::count();
            $queryTime = (microtime(true) - $start) * 1000;

            return [
                'status' => 'healthy',
                'connection' => 'active',
                'query_time_ms' => round($queryTime, 2),
                'total_services' => $count
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'connection' => 'failed',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check cache health
     */
    private function checkCacheHealth()
    {
        try {
            $testKey = 'health_check_' . time();
            $testValue = 'test_value_' . rand(1000, 9999);

            $start = microtime(true);
            Cache::put($testKey, $testValue, 60);
            $retrieved = Cache::get($testKey);
            $cacheTime = (microtime(true) - $start) * 1000;

            Cache::forget($testKey);

            return [
                'status' => $retrieved === $testValue ? 'healthy' : 'degraded',
                'driver' => config('cache.default'),
                'response_time_ms' => round($cacheTime, 2)
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'driver' => config('cache.default'),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get API performance metrics
     */
    private function getApiPerformanceMetrics()
    {
        return [
            'memory_usage' => [
                'current_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
                'peak_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
                'limit' => ini_get('memory_limit')
            ],
            'execution_time' => [
                'average_response_time_ms' => 150, // This would be calculated from logs
                'slow_queries_threshold_ms' => 1000
            ]
        ];
    }

    /**
     * Check data integrity
     */
    private function checkDataIntegrity()
    {
        try {
            $issues = [];

            // Check for orphaned part records
            $orphanedParts = DetailPartServices::whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                      ->from('sevices')
                      ->whereRaw('sevices.id = detail_part_services.kode_services');
            })->count();

            if ($orphanedParts > 0) {
                $issues[] = "Found {$orphanedParts} orphaned part records";
            }

            // Check for services without technicians (completed status)
            $servicesWithoutTechnicians = modelServices::where('status_services', 'Selesai')
                ->whereNull('id_teknisi')
                ->count();

            if ($servicesWithoutTechnicians > 0) {
                $issues[] = "Found {$servicesWithoutTechnicians} completed services without technicians";
            }

            // Check for negative stock
            $negativeStock = Sparepart::where('stok_sparepart', '<', 0)->count();

            if ($negativeStock > 0) {
                $issues[] = "Found {$negativeStock} items with negative stock";
            }

            return [
                'status' => empty($issues) ? 'healthy' : 'warning',
                'issues_found' => count($issues),
                'issues' => $issues
            ];

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get system resource usage
     */
    private function getSystemResourceUsage()
    {
        return [
            'php_version' => PHP_VERSION,
            'laravel_version' => app()->version(),
            'memory_limit' => ini_get('memory_limit'),
            'max_execution_time' => ini_get('max_execution_time'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size')
        ];
    }

    /**
     * Calculate overall health score
     */
    private function calculateOverallHealth($healthData)
    {
        $scores = [];

        // Database health score
        $scores[] = $healthData['database_status']['status'] === 'healthy' ? 100 : 0;

        // Cache health score
        $scores[] = $healthData['cache_status']['status'] === 'healthy' ? 100 :
                   ($healthData['cache_status']['status'] === 'degraded' ? 50 : 0);

        // Data integrity score
        $scores[] = $healthData['data_integrity']['status'] === 'healthy' ? 100 :
                   ($healthData['data_integrity']['status'] === 'warning' ? 70 : 0);

        $averageScore = array_sum($scores) / count($scores);

        if ($averageScore >= 90) {
            return 'excellent';
        } elseif ($averageScore >= 70) {
            return 'good';
        } elseif ($averageScore >= 50) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * Get trending services data
     */
    public function getTrendingServices(Request $request)
    {
        try {
            $period = $request->get('period', 'week');
            $limit = min($request->get('limit', 10), 20);

            $cacheKey = "trending_services_{$this->getThisUser()->id_upline}_{$period}_{$limit}";

            $trending = Cache::remember($cacheKey, 300, function () use ($period, $limit) {
                $dateRange = $this->getDateRangeForPeriod($period);

                // Most frequent device types
                $deviceTrends = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->whereBetween('created_at', $dateRange)
                    ->selectRaw('type_unit, COUNT(*) as frequency, AVG(total_biaya) as avg_cost')
                    ->groupBy('type_unit')
                    ->orderByDesc('frequency')
                    ->limit($limit)
                    ->get();

                // Most profitable services
                $profitableServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('status_services', 'Selesai')
                    ->whereBetween('updated_at', $dateRange)
                    ->orderByDesc('total_biaya')
                    ->limit($limit)
                    ->get();

                // Fastest completion times
                $fastestServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                    ->where('status_services', 'Selesai')
                    ->whereBetween('updated_at', $dateRange)
                    ->selectRaw('*, TIMESTAMPDIFF(HOUR, created_at, updated_at) as completion_hours')
                    ->havingRaw('completion_hours > 0')
                    ->orderBy('completion_hours', 'asc')
                    ->limit($limit)
                    ->get();

                return [
                    'device_trends' => $deviceTrends,
                    'profitable_services' => $profitableServices,
                    'fastest_completions' => $fastestServices,
                    'period' => $period,
                    'date_range' => $dateRange
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $trending
            ]);

        } catch (\Exception $e) {
            Log::error("Get Trending Services Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching trending data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service recommendations based on patterns
     */
    public function getServiceRecommendations(Request $request)
    {
        try {
            $serviceId = $request->get('service_id');
            $deviceType = $request->get('device_type');

            $cacheKey = "service_recommendations_{$this->getThisUser()->id_upline}_{$serviceId}_{$deviceType}";

            $recommendations = Cache::remember($cacheKey, 600, function () use ($serviceId, $deviceType) {
                $recommendations = [];

                if ($serviceId) {
                    $service = modelServices::find($serviceId);
                    if ($service) {
                        $deviceType = $service->type_unit;
                    }
                }

                if ($deviceType) {
                    // Find common parts used for this device type
                    $commonParts = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                        ->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                        ->where('sevices.type_unit', 'LIKE', "%{$deviceType}%")
                        ->where('sevices.kode_owner', $this->getThisUser()->id_upline)
                        ->selectRaw('spareparts.nama_sparepart, COUNT(*) as usage_count, AVG(detail_part_services.detail_harga_part_service) as avg_price')
                        ->groupBy('spareparts.id', 'spareparts.nama_sparepart')
                        ->orderByDesc('usage_count')
                        ->limit(10)
                        ->get();

                    $recommendations['common_parts'] = $commonParts;

                    // Find typical service costs for this device
                    $typicalCosts = modelServices::where('type_unit', 'LIKE', "%{$deviceType}%")
                        ->where('kode_owner', $this->getThisUser()->id_upline)
                        ->where('status_services', 'Selesai')
                        ->selectRaw('AVG(total_biaya) as avg_cost, MIN(total_biaya) as min_cost, MAX(total_biaya) as max_cost')
                        ->first();

                    $recommendations['typical_costs'] = $typicalCosts;

                    // Find average completion time
                    $avgCompletionTime = modelServices::where('type_unit', 'LIKE', "%{$deviceType}%")
                        ->where('kode_owner', $this->getThisUser()->id_upline)
                        ->where('status_services', 'Selesai')
                        ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, created_at, updated_at)) as avg_hours')
                        ->value('avg_hours');

                    $recommendations['avg_completion_hours'] = round($avgCompletionTime ?? 0, 2);
                }

                return $recommendations;
            });

            return response()->json([
                'success' => true,
                'data' => $recommendations,
                'device_type' => $deviceType
            ]);

        } catch (\Exception $e) {
            Log::error("Get Service Recommendations Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error fetching recommendations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Bulk update services
     */
    public function bulkUpdateServices(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_ids' => 'required|array|min:1',
                'service_ids.*' => 'integer|exists:sevices,id',
                'action' => 'required|in:update_status,assign_technician,update_priority',
                'data' => 'required|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceIds = $request->service_ids;
            $action = $request->action;
            $data = $request->data;
            $results = [];

            DB::beginTransaction();

            foreach ($serviceIds as $serviceId) {
                try {
                    $service = modelServices::where('id', $serviceId)
                        ->where('kode_owner', $this->getThisUser()->id_upline)
                        ->first();

                    if (!$service) {
                        $results[$serviceId] = [
                            'success' => false,
                            'message' => 'Service not found or unauthorized'
                        ];
                        continue;
                    }

                    switch ($action) {
                        case 'update_status':
                            if (isset($data['status'])) {
                                $service->update(['status_services' => $data['status']]);
                                $results[$serviceId] = [
                                    'success' => true,
                                    'message' => 'Status updated'
                                ];
                            }
                            break;

                        case 'assign_technician':
                            if (isset($data['technician_id'])) {
                                $service->update(['id_teknisi' => $data['technician_id']]);
                                $results[$serviceId] = [
                                    'success' => true,
                                    'message' => 'Technician assigned'
                                ];
                            }
                            break;

                        case 'update_priority':
                            if (isset($data['priority'])) {
                                // Assuming you have a priority field
                                $service->update(['priority' => $data['priority']]);
                                $results[$serviceId] = [
                                    'success' => true,
                                    'message' => 'Priority updated'
                                ];
                            }
                            break;
                    }

                } catch (\Exception $e) {
                    $results[$serviceId] = [
                        'success' => false,
                        'message' => $e->getMessage()
                    ];
                }
            }

            DB::commit();

            // Clear relevant caches
            $this->clearServiceCaches(new Request(['types' => ['completed', 'statistics']]));

            $successCount = count(array_filter($results, function($result) {
                return $result['success'];
            }));

            return response()->json([
                'success' => true,
                'message' => "Bulk update completed. {$successCount} out of " . count($serviceIds) . " services updated successfully.",
                'results' => $results,
                'summary' => [
                    'total_processed' => count($serviceIds),
                    'successful' => $successCount,
                    'failed' => count($serviceIds) - $successCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Bulk Update Services Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Bulk update failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getDailyReportServices(Request $request)
{
    try {
        $page = $request->get('page', 1);
        $limit = min($request->get('limit', 20), 50);
        $search = $request->get('search', '');
        $technician_id = $request->get('technician_id');
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $status = $request->get('status'); // 'Selesai', 'Diambil', atau kosong untuk semua

        $cacheKey = "daily_report_{$this->getThisUser()->id_upline}_{$page}_{$limit}_" .
                   md5($search . $technician_id . $date_from . $date_to . $status);

        $result = Cache::remember($cacheKey, 300, function () use ($page, $limit, $search, $technician_id, $date_from, $date_to, $status) {
            $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                ->whereIn('status_services', ['Selesai', 'Diambil']) // Ambil kedua status
                ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                ->select(
                    'sevices.*',
                    'users.name as teknisi',
                    'sevices.status_services as service_status'
                );

            // Filter by specific status if provided
            if (!empty($status)) {
                $query->where('sevices.status_services', $status);
            }

            // Apply search filters
            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('sevices.nama_pelanggan', 'LIKE', "%$search%")
                      ->orWhere('sevices.type_unit', 'LIKE', "%$search%")
                      ->orWhere('sevices.kode_service', 'LIKE', "%$search%")
                      ->orWhere('sevices.keterangan', 'LIKE', "%$search%");
                });
            }

            if ($technician_id) {
                $query->where('sevices.id_teknisi', $technician_id);
            }

            if ($date_from) {
                $query->whereDate('sevices.updated_at', '>=', $date_from);
            }

            if ($date_to) {
                $query->whereDate('sevices.updated_at', '<=', $date_to);
            }

            $totalCount = $query->count();
            $services = $query->orderBy('sevices.updated_at', 'desc')
                             ->offset(($page - 1) * $limit)
                             ->limit($limit)
                             ->get();

            // FIXED: Add computed fields with CORRECT payment logic
            $services = $services->map(function ($service) {
                $totalBiaya = (float) $service->total_biaya;
                $dp = (float) $service->dp;
                $sisaBayar = $totalBiaya - $dp;
                $isLunas = $sisaBayar <= 0;

                // FIXED: Correct omset calculation based on business logic
                $omsetValue = 0;
                if ($service->service_status === 'Diambil') {
                    // Service Diambil = PASTI LUNAS = uang masuk = total_biaya
                    $omsetValue = $totalBiaya;
                } elseif ($service->service_status === 'Selesai') {
                    // Service Selesai = cek status pembayaran
                    $omsetValue = $isLunas ? $totalBiaya : $dp;
                } else {
                    // Status lainnya
                    $omsetValue = $isLunas ? $totalBiaya : $dp;
                }

                return [
                    'id' => $service->id,
                    'kode_service' => $service->kode_service,
                    'nama_pelanggan' => $service->nama_pelanggan,
                    'type_unit' => $service->type_unit,
                    'teknisi' => $service->teknisi,
                    'id_teknisi' => $service->id_teknisi,
                    'total_biaya' => $totalBiaya,
                    'dp' => $dp,
                    'sisa_bayar' => $sisaBayar,
                    'is_lunas' => $isLunas || $service->service_status === 'Diambil', // Diambil = pasti lunas
                    'service_status' => $service->service_status,
                    'keterangan' => $service->keterangan,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                    // FIXED: Correct omset value calculation
                    'omset_value' => $omsetValue,
                ];
            });

            return [
                'data' => $services,
                'pagination' => [
                    'current_page' => (int) $page,
                    'per_page' => (int) $limit,
                    'total' => (int) $totalCount,
                    'total_pages' => ceil($totalCount / $limit),
                    'has_next_page' => $page < ceil($totalCount / $limit),
                    'has_previous_page' => $page > 1,
                ]
            ];
        });

        // Calculate summary with corrected logic
        $summary = $this->calculateDailySummary($date_from, $date_to);

        return response()->json([
            'success' => true,
            'message' => 'Data layanan harian berhasil diambil.',
            'data' => $result['data'],
            'pagination' => $result['pagination'],
            'summary' => $summary,
            'filters' => [
                'search' => $search,
                'technician_id' => $technician_id,
                'date_from' => $date_from,
                'date_to' => $date_to,
                'status' => $status
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error("Get Daily Report Services Error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        ], 500);
    }
}

/**
 * FIXED: Calculate summary for daily report with CORRECT payment logic
 */
private function calculateDailySummary($date_from, $date_to)
{
    try {
        $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereIn('status_services', ['Selesai', 'Diambil']);

        if ($date_from) {
            $query->whereDate('updated_at', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('updated_at', '<=', $date_to);
        }

        $services = $query->get();

        $summary = [
            'total_services' => $services->count(),
            'completed_services' => $services->where('status_services', 'Selesai')->count(),
            'taken_services' => $services->where('status_services', 'Diambil')->count(),
            'total_omset' => 0, // Uang yang benar-benar masuk
            'completed_omset' => 0,
            'taken_omset' => 0,
            'total_dp_collected' => 0,
            'total_remaining_payment' => 0, // FIXED: Sisa tagihan yang benar
        ];

        foreach ($services as $service) {
            $totalBiaya = (float) $service->total_biaya;
            $dp = (float) $service->dp;
            $sisaBayar = $totalBiaya - $dp;
            $isLunas = $sisaBayar <= 0;

            // Total DP collected
            $summary['total_dp_collected'] += $dp;

            if ($service->status_services === 'Diambil') {
                // FIXED: Service Diambil = PASTI LUNAS
                $omsetValue = $totalBiaya; // Uang masuk = total biaya
                $summary['taken_omset'] += $omsetValue;
                $summary['total_omset'] += $omsetValue;
                // PENTING: Service diambil TIDAK ada sisa tagihan
                // $summary['total_remaining_payment'] += 0; // Tidak menambah sisa tagihan

                Log::info("Service Diambil: {$service->nama_pelanggan} - Omset: {$omsetValue} - Sisa: 0");

            } elseif ($service->status_services === 'Selesai') {
                // FIXED: Service Selesai = cek status pembayaran
                if ($isLunas) {
                    // Selesai dan sudah lunas
                    $omsetValue = $totalBiaya; // Uang masuk = total biaya
                    $summary['completed_omset'] += $omsetValue;
                    $summary['total_omset'] += $omsetValue;
                    // Tidak ada sisa tagihan karena sudah lunas

                    Log::info("Service Selesai (Lunas): {$service->nama_pelanggan} - Omset: {$omsetValue} - Sisa: 0");
                } else {
                    // Selesai tapi belum lunas
                    $omsetValue = $dp; // Uang masuk = DP saja
                    $summary['completed_omset'] += $omsetValue;
                    $summary['total_omset'] += $omsetValue;
                    $summary['total_remaining_payment'] += $sisaBayar; // Ada sisa tagihan

                    Log::info("Service Selesai (Belum Lunas): {$service->nama_pelanggan} - Omset: {$omsetValue} - Sisa: {$sisaBayar}");
                }
            }
        }

        // VALIDASI: Pastikan tidak ada sisa tagihan jika semua service diambil
        if ($summary['taken_services'] > 0 && $summary['completed_services'] == 0 && $summary['total_remaining_payment'] > 0) {
            Log::warning("INCONSISTENCY: Ada service diambil tapi masih ada sisa tagihan!");
            // Reset sisa tagihan jika hanya ada service diambil
            $summary['total_remaining_payment'] = 0;
        }

        Log::info("Summary Calculation:", $summary);

        return $summary;
    } catch (\Exception $e) {
        Log::error("Calculate Daily Summary Error: " . $e->getMessage());
        return [
            'total_services' => 0,
            'completed_services' => 0,
            'taken_services' => 0,
            'total_omset' => 0,
            'completed_omset' => 0,
            'taken_omset' => 0,
            'total_dp_collected' => 0,
            'total_remaining_payment' => 0,
        ];
    }
}

/**
 * FIXED: Get daily report grouped by date with CORRECT payment logic
 * Endpoint: GET /api/services/dailyReportGrouped
 */
public function getDailyReportGrouped(Request $request)
{
    try {
        $date_from = $request->get('date_from');
        $date_to = $request->get('date_to');
        $technician_id = $request->get('technician_id');

        $query = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereIn('status_services', ['Selesai', 'Diambil'])
            ->join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select(
                'sevices.*',
                'users.name as teknisi',
                'sevices.status_services as service_status'
            );

        if ($technician_id) {
            $query->where('sevices.id_teknisi', $technician_id);
        }

        if ($date_from) {
            $query->whereDate('sevices.updated_at', '>=', $date_from);
        }

        if ($date_to) {
            $query->whereDate('sevices.updated_at', '<=', $date_to);
        }

        $services = $query->orderBy('sevices.updated_at', 'desc')->get();

        // Group by date
        $groupedServices = $services->groupBy(function ($service) {
            return Carbon::parse($service->updated_at)->format('Y-m-d');
        });

        $result = [];
        foreach ($groupedServices as $date => $dateServices) {
            $dailyOmset = 0;
            $processedServices = [];

            foreach ($dateServices as $service) {
                $totalBiaya = (float) $service->total_biaya;
                $dp = (float) $service->dp;
                $sisaBayar = $totalBiaya - $dp;
                $isLunas = $sisaBayar <= 0;

                // FIXED: Correct omset calculation
                $omsetValue = 0;
                if ($service->service_status === 'Diambil') {
                    // Service Diambil = PASTI LUNAS
                    $omsetValue = $totalBiaya;
                } elseif ($service->service_status === 'Selesai') {
                    // Service Selesai = cek status pembayaran
                    $omsetValue = $isLunas ? $totalBiaya : $dp;
                } else {
                    // Status lainnya
                    $omsetValue = $isLunas ? $totalBiaya : $dp;
                }

                $dailyOmset += $omsetValue;

                $processedServices[] = [
                    'id' => $service->id,
                    'kode_service' => $service->kode_service,
                    'nama_pelanggan' => $service->nama_pelanggan,
                    'type_unit' => $service->type_unit,
                    'teknisi' => $service->teknisi,
                    'total_biaya' => $totalBiaya,
                    'dp' => $dp,
                    'sisa_bayar' => $sisaBayar,
                    'is_lunas' => $isLunas || $service->service_status === 'Diambil', // Diambil = pasti lunas
                    'service_status' => $service->service_status,
                    'omset_value' => $omsetValue, // FIXED: Correct omset value
                    'updated_at' => $service->updated_at,
                ];
            }

            $result[] = [
                'date' => $date,
                'date_formatted' => Carbon::parse($date)->format('d F Y'),
                'day_name' => Carbon::parse($date)->locale('id')->dayName,
                'total_services' => count($processedServices),
                'completed_services' => collect($processedServices)->where('service_status', 'Selesai')->count(),
                'taken_services' => collect($processedServices)->where('service_status', 'Diambil')->count(),
                'daily_omset' => $dailyOmset, // FIXED: Correct daily omset
                'services' => $processedServices,
            ];
        }

        // Sort by date descending
        usort($result, function ($a, $b) {
            return strcmp($b['date'], $a['date']);
        });

        $summary = $this->calculateDailySummary($date_from, $date_to);

        return response()->json([
            'success' => true,
            'message' => 'Data laporan harian berhasil diambil.',
            'data' => $result,
            'summary' => $summary,
            'filters' => [
                'technician_id' => $technician_id,
                'date_from' => $date_from,
                'date_to' => $date_to,
            ]
        ], 200);
    } catch (\Exception $e) {
        Log::error("Get Daily Report Grouped Error: " . $e->getMessage());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        ], 500);
    }
}

} // End of ServiceApiController class
