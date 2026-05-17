<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Traits\KategoriLaciTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

/**
 * DeviceMonitoringApiController
 * 
 * Mengelola semua operasi terkait Monitoring Device/Unit Service:
 * - Device trends & statistics
 * - Device comparison antar periode
 * - Daily device monitoring
 * - Device pickup alerts & status updates
 * 
 * Dipindahkan dari FinancialReportApiController untuk pemisahan domain
 * Operasional Toko vs Keuangan Perusahaan.
 */
class DeviceMonitoringApiController extends Controller
{
    use KategoriLaciTrait;

    /**
     * Get device trends over time
     */
    public function getDeviceTrends(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'device_types' => 'sometimes|array',
                'device_types.*' => 'string',
                'interval' => 'sometimes|in:day,week,month'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->tgl_awal;
            $endDate = $request->tgl_akhir;
            $deviceTypes = $request->get('device_types', []);
            $interval = $request->get('interval', 'day');

            $kodeOwner = $this->getKodeOwner();

            Log::info('Device Trends Request', [
                'user_id' => auth()->user()->id,
                'period' => $startDate . ' to ' . $endDate,
                'interval' => $interval,
                'device_types' => $deviceTypes
            ]);

            $query = DB::table('sevices')
                ->where('kode_owner', $kodeOwner)
                ->whereIn('status_services', ['Selesai', 'Diambil'])
                ->whereBetween('tgl_service', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59'
                ]);

            if (!empty($deviceTypes)) {
                $query->whereIn('type_unit', $deviceTypes);
            }

            $dateFormat = $this->getDateFormat($interval);

            $trends = $query
                ->select([
                    DB::raw("DATE_FORMAT(tgl_service, '{$dateFormat}') as period"),
                    'type_unit',
                    DB::raw('COUNT(*) as service_count'),
                    DB::raw('SUM(total_biaya) as revenue'),
                    DB::raw('AVG(total_biaya) as avg_revenue')
                ])
                ->groupBy('period', 'type_unit')
                ->orderBy('period')
                ->orderBy('revenue', 'desc')
                ->get();

            $trendsByPeriod = [];
            foreach ($trends as $trend) {
                if (!isset($trendsByPeriod[$trend->period])) {
                    $trendsByPeriod[$trend->period] = [
                        'period' => $trend->period,
                        'total_services' => 0,
                        'total_revenue' => 0,
                        'devices' => []
                    ];
                }

                $trendsByPeriod[$trend->period]['total_services'] += $trend->service_count;
                $trendsByPeriod[$trend->period]['total_revenue'] += $trend->revenue;
                $trendsByPeriod[$trend->period]['devices'][] = [
                    'type_unit' => $trend->type_unit,
                    'service_count' => $trend->service_count,
                    'revenue' => $trend->revenue,
                    'avg_revenue' => round($trend->avg_revenue, 2)
                ];
            }

            return response()->json([
                'success' => true,
                'message' => 'Device trends retrieved successfully',
                'data' => [
                    'trends_by_period' => array_values($trendsByPeriod),
                    'trends_raw' => $trends,
                    'metadata' => [
                        'interval' => $interval,
                        'device_types_filter' => $deviceTypes,
                        'total_periods' => count($trendsByPeriod)
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Get Device Trends Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device trends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get device comparison report
     */
    public function getDeviceComparison(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'period1_start' => 'required|date',
                'period1_end' => 'required|date|after_or_equal:period1_start',
                'period2_start' => 'required|date',
                'period2_end' => 'required|date|after_or_equal:period2_start',
                'device_types' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $kodeOwner = $this->getKodeOwner();
            $deviceTypes = $request->get('device_types', []);

            $period1Data = $this->getDeviceDataForPeriod($kodeOwner, $request->period1_start, $request->period1_end, $deviceTypes);
            $period2Data = $this->getDeviceDataForPeriod($kodeOwner, $request->period2_start, $request->period2_end, $deviceTypes);

            $comparison = $this->calculateDeviceComparison($period1Data, $period2Data);

            return response()->json([
                'success' => true,
                'message' => 'Device comparison retrieved successfully',
                'data' => [
                    'period1' => [
                        'date_range' => $request->period1_start . ' to ' . $request->period1_end,
                        'data' => $period1Data
                    ],
                    'period2' => [
                        'date_range' => $request->period2_start . ' to ' . $request->period2_end,
                        'data' => $period2Data
                    ],
                    'comparison' => $comparison
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Get Device Comparison Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device comparison',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Daily device monitoring
     */
    public function getDailyDeviceMonitoring(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'date' => 'required|date',
                'status_filter' => 'sometimes|in:all,picked_up,pending',
                'technician_id' => 'sometimes|integer',
                'device_type' => 'sometimes|string'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $date = $request->date;
            $statusFilter = $request->get('status_filter', 'all');
            $technicianId = $request->get('technician_id');
            $deviceType = $request->get('device_type');
            $kodeOwner = $this->getKodeOwner();

            $baseQuery = DB::table('sevices')
                ->leftJoin('users', 'sevices.id_teknisi', '=', 'users.id')
                ->where('sevices.kode_owner', $kodeOwner)
                ->whereIn('sevices.status_services', ['Selesai', 'Diambil'])
                ->whereDate('sevices.tgl_service', $date);

            if ($technicianId) { $baseQuery->where('sevices.id_teknisi', $technicianId); }
            if ($deviceType) { $baseQuery->where('sevices.type_unit', 'LIKE', "%{$deviceType}%"); }

            $devices = $baseQuery
                ->select([
                    'sevices.id as service_id', 'sevices.kode_service', 'sevices.nama_pelanggan as customer_name',
                    'sevices.type_unit', 'sevices.total_biaya as total_cost', 'sevices.dp',
                    'sevices.tgl_service as completed_at', 'sevices.status_services', 'sevices.updated_at',
                    'users.name as technician_name', 'users.id as technician_id'
                ])
                ->orderByDesc('sevices.tgl_service')
                ->get()
                ->map(function($device) {
                    if ($device->status_services === 'Diambil') {
                        $device->pickup_status = 'picked_up';
                        $device->picked_up_at = $device->updated_at;
                    } else {
                        $device->pickup_status = 'pending';
                        $device->picked_up_at = null;
                    }
                    $device->days_since_completion = Carbon::parse($device->completed_at)->diffInDays(Carbon::now());
                    $device->completion_hour = Carbon::parse($device->completed_at)->format('H:i');
                    $device->pickup_hour = $device->picked_up_at ? Carbon::parse($device->picked_up_at)->format('H:i') : null;
                    $device->same_day_pickup = false;
                    if ($device->picked_up_at) {
                        $device->same_day_pickup = (Carbon::parse($device->completed_at)->format('Y-m-d') === Carbon::parse($device->picked_up_at)->format('Y-m-d'));
                    }
                    return $device;
                });

            if ($statusFilter !== 'all') {
                $devices = $devices->filter(fn($d) => $d->pickup_status === $statusFilter);
            }

            $totalDevices = $devices->count();
            $pickedUpDevices = $devices->where('pickup_status', 'picked_up')->count();
            $pendingDevices = $devices->where('pickup_status', 'pending')->count();
            $totalRevenue = $devices->sum('total_cost');

            $devicesByTechnician = $devices->groupBy('technician_id')->map(fn($techDevices) => [
                'technician_name' => $techDevices->first()->technician_name ?: 'Unknown',
                'total_devices' => $techDevices->count(),
                'picked_up' => $techDevices->where('pickup_status', 'picked_up')->count(),
                'pending' => $techDevices->where('pickup_status', 'pending')->count(),
                'total_revenue' => $techDevices->sum('total_cost')
            ])->values();

            $devicesByType = $devices->groupBy('type_unit')->map(fn($typeDevices) => [
                'device_type' => $typeDevices->first()->type_unit,
                'total_devices' => $typeDevices->count(),
                'picked_up' => $typeDevices->where('pickup_status', 'picked_up')->count(),
                'pending' => $typeDevices->where('pickup_status', 'pending')->count(),
                'total_revenue' => $typeDevices->sum('total_cost'),
                'avg_cost' => round($typeDevices->avg('total_cost'), 2)
            ])->values();

            $hourlyDistribution = $devices->groupBy(fn($d) => Carbon::parse($d->completed_at)->format('H'))->map(fn($hd, $h) => [
                'hour' => $h . ':00', 'count' => $hd->count(), 'revenue' => $hd->sum('total_cost')
            ])->sortBy('hour')->values();

            return response()->json([
                'success' => true,
                'message' => 'Daily device monitoring data retrieved successfully',
                'data' => [
                    'summary' => [
                        'total_devices' => $totalDevices,
                        'picked_up_devices' => $pickedUpDevices,
                        'pending_devices' => $pendingDevices,
                        'total_revenue' => (float) $totalRevenue,
                        'picked_up_revenue' => (float) $devices->where('pickup_status', 'picked_up')->sum('total_cost'),
                        'pending_revenue' => (float) $devices->where('pickup_status', 'pending')->sum('total_cost'),
                        'pickup_rate' => $totalDevices > 0 ? round(($pickedUpDevices / $totalDevices) * 100, 2) : 0,
                        'avg_revenue_per_device' => $totalDevices > 0 ? round($totalRevenue / $totalDevices, 2) : 0
                    ],
                    'devices' => $devices->map(fn($d) => [
                        'service_id' => $d->kode_service, 'customer_name' => $d->customer_name,
                        'type_unit' => $d->type_unit, 'technician_name' => $d->technician_name ?: 'Unknown',
                        'technician_id' => $d->technician_id, 'total_cost' => (float) $d->total_cost,
                        'dp' => (float) $d->dp, 'remaining_payment' => (float) ($d->total_cost - $d->dp),
                        'status' => $d->pickup_status, 'completed_at' => $d->completed_at,
                        'picked_up_at' => $d->picked_up_at, 'days_since_completion' => $d->days_since_completion,
                        'completion_hour' => $d->completion_hour, 'pickup_hour' => $d->pickup_hour,
                        'same_day_pickup' => $d->same_day_pickup
                    ])->values(),
                    'analytics' => [
                        'by_technician' => $devicesByTechnician,
                        'by_device_type' => $devicesByType,
                        'hourly_distribution' => $hourlyDistribution
                    ],
                    'metadata' => [
                        'date' => $date,
                        'day_name' => Carbon::parse($date)->locale('id')->dayName,
                        'status_filter' => $statusFilter,
                        'total_devices_before_filter' => $devices->count()
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Daily Device Monitoring Error', ['user_id' => auth()->user()->id ?? null, 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['success' => false, 'message' => 'Error retrieving daily device monitoring data', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Get device pickup alerts
     */
    public function getDevicePickupAlerts(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), ['days_threshold' => 'sometimes|integer|min:1|max:30']);
            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $daysThreshold = $request->get('days_threshold', 3);
            $kodeOwner = $this->getKodeOwner();

            $alertDevices = DB::table('sevices')
                ->leftJoin('users', 'sevices.id_teknisi', '=', 'users.id')
                ->where('sevices.kode_owner', $kodeOwner)
                ->where('sevices.status_services', 'Selesai')
                ->where('sevices.tgl_service', '<=', Carbon::now()->subDays($daysThreshold))
                ->select([
                    'sevices.kode_service', 'sevices.nama_pelanggan', 'sevices.type_unit',
                    'sevices.total_biaya', 'sevices.no_wa_pelanggan',
                    'sevices.tgl_service as completed_at', 'users.name as technician_name',
                    'users.id as technician_id',
                    DB::raw('DATEDIFF(NOW(), sevices.tgl_service) as days_since_completion')
                ])
                ->orderByDesc('days_since_completion')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Device pickup alerts retrieved successfully',
                'data' => [
                    'alerts' => $alertDevices,
                    'summary' => [
                        'total_devices' => $alertDevices->count(),
                        'total_pending_revenue' => (float) $alertDevices->sum('total_biaya'),
                        'average_days_pending' => round($alertDevices->avg('days_since_completion'), 1),
                        'days_threshold' => $daysThreshold
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Device Pickup Alerts Error', ['user_id' => auth()->user()->id ?? null, 'error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error retrieving device pickup alerts', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    /**
     * Update device pickup status manually
     */
    public function updateDevicePickupStatus(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'service_id' => 'required|string',
                'action' => 'required|in:mark_picked_up,mark_pending',
                'pickup_date' => 'sometimes|date',
                'notes' => 'sometimes|string|max:500'
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
            }

            $kodeOwner = $this->getKodeOwner();
            $service = DB::table('sevices')->where('kode_service', $request->service_id)->where('kode_owner', $kodeOwner)->first();

            if (!$service) {
                return response()->json(['success' => false, 'message' => 'Service not found'], 404);
            }

            $updateData = ($request->action === 'mark_picked_up')
                ? ['status_services' => 'Diambil', 'updated_at' => $request->get('pickup_date', Carbon::now())]
                : ['status_services' => 'Selesai', 'updated_at' => Carbon::now()];

            Log::info('Manual Device Status Update', [
                'user_id' => auth()->user()->id, 'service_id' => $request->service_id,
                'action' => $request->action, 'previous_status' => $service->status_services
            ]);

            DB::table('sevices')->where('kode_service', $request->service_id)->where('kode_owner', $kodeOwner)->update($updateData);

            $serviceCompletionDate = Carbon::parse($service->tgl_service)->format('Y-m-d');
            Cache::forget("daily_device_monitoring_{$kodeOwner}_{$serviceCompletionDate}_all__");

            return response()->json([
                'success' => true,
                'message' => 'Device pickup status updated successfully',
                'data' => [
                    'service_id' => $request->service_id,
                    'new_status' => $updateData['status_services'],
                    'pickup_date' => $request->action === 'mark_picked_up' ? $updateData['updated_at'] : null,
                    'action' => $request->action
                ]
            ]);
        } catch (\Exception $e) {
            Log::error('Update Device Pickup Status Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Error updating device pickup status', 'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'], 500);
        }
    }

    // ========== PRIVATE HELPER METHODS ==========

    private function getKodeOwner()
    {
        return auth()->user()->userDetail->id_upline ?? auth()->user()->id;
    }

    private function getDateFormat($interval)
    {
        switch ($interval) {
            case 'week': return '%Y-%u';
            case 'month': return '%Y-%m';
            default: return '%Y-%m-%d';
        }
    }

    private function getDeviceDataForPeriod($kodeOwner, $startDate, $endDate, $deviceTypes = [])
    {
        $query = DB::table('sevices')
            ->where('kode_owner', $kodeOwner)
            ->whereIn('status_services', ['Selesai', 'Diambil'])
            ->whereBetween('tgl_service', [$startDate . ' 00:00:00', $endDate . ' 23:59:59']);

        if (!empty($deviceTypes)) {
            $query->whereIn('type_unit', $deviceTypes);
        }

        return $query
            ->select([
                'type_unit',
                DB::raw('COUNT(*) as total_services'),
                DB::raw('SUM(total_biaya) as total_revenue'),
                DB::raw('AVG(total_biaya) as avg_revenue')
            ])
            ->groupBy('type_unit')
            ->orderByDesc('total_revenue')
            ->get();
    }

    private function calculateDeviceComparison($period1Data, $period2Data)
    {
        $comparison = [];
        $period2Lookup = [];
        foreach ($period2Data as $item) { $period2Lookup[$item->type_unit] = $item; }

        foreach ($period1Data as $item1) {
            $deviceType = $item1->type_unit;
            $item2 = $period2Lookup[$deviceType] ?? null;
            $comparison[$deviceType] = [
                'device_type' => $deviceType,
                'period1' => ['services' => $item1->total_services, 'revenue' => $item1->total_revenue, 'avg_revenue' => round($item1->avg_revenue, 2)],
                'period2' => ['services' => $item2 ? $item2->total_services : 0, 'revenue' => $item2 ? $item2->total_revenue : 0, 'avg_revenue' => $item2 ? round($item2->avg_revenue, 2) : 0],
                'changes' => [
                    'services_change' => $this->calculatePercentageChange($item1->total_services, $item2 ? $item2->total_services : 0),
                    'revenue_change' => $this->calculatePercentageChange($item1->total_revenue, $item2 ? $item2->total_revenue : 0),
                    'avg_revenue_change' => $this->calculatePercentageChange($item1->avg_revenue, $item2 ? $item2->avg_revenue : 0)
                ]
            ];
        }

        foreach ($period2Data as $item2) {
            if (!isset($comparison[$item2->type_unit])) {
                $comparison[$item2->type_unit] = [
                    'device_type' => $item2->type_unit,
                    'period1' => ['services' => 0, 'revenue' => 0, 'avg_revenue' => 0],
                    'period2' => ['services' => $item2->total_services, 'revenue' => $item2->total_revenue, 'avg_revenue' => round($item2->avg_revenue, 2)],
                    'changes' => ['services_change' => 100, 'revenue_change' => 100, 'avg_revenue_change' => 100]
                ];
            }
        }

        return array_values($comparison);
    }

    private function calculatePercentageChange($oldValue, $newValue)
    {
        if ($oldValue == 0) { return $newValue > 0 ? 100 : 0; }
        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
}
