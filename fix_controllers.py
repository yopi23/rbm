import re

with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Admin/DashboardController.php', 'r', encoding='utf-8') as f:
    text = f.read()

original_get_pending = """    public function get_pending_services(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 0);
            $query = modelServices::with('customer')
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->whereIn('status_services', ['Antri', 'Proses'])
                ->latest();

            if ($perPage > 0) {
                $paginated = $query->paginate($perPage);
                return response()->json([
                    'success' => true,
                    'status' => 'success',
                    'message' => 'Data service yang antri berhasil diambil.',
                    'data' => $paginated->items(),
                    'pagination' => [
                        'current_page' => $paginated->currentPage(),
                        'last_page' => $paginated->lastPage(),
                        'total' => $paginated->total(),
                        'per_page' => $paginated->perPage(),
                    ]
                ], 200);
            }

            $services = $query->get();

            return response()->json([
                'success' => true,
                'status' => 'success',
                'message' => $services->isEmpty() ? 'Tidak ada data service yang antri.' : 'Data service yang antri berhasil diambil.',
                'data' => $services,
            ], 200);
        } catch (\Exception $e) {
            \Log::error('get_pending_services error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Gagal mengambil data service antri: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }"""

new_get_pending = """    public function get_pending_services(Request $request)
    {
        try {
            $perPage = (int) $request->query('per_page', 0);
            $uplineId = $this->getThisUser()->id_upline;
            
            // Menggunakan versioning agar support semua driver cache
            $cacheVersion = \Illuminate\Support\Facades\Cache::rememberForever("service_cache_version_{$uplineId}", function() { return 1; });
            $cacheKey = "pending_services_{$uplineId}_{$perPage}_v{$cacheVersion}";

            $result = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function() use ($perPage, $uplineId) {
                $query = \App\Models\Sevices::with('customer')
                    ->where('kode_owner', $uplineId)
                    ->whereIn('status_services', ['Antri', 'Proses'])
                    ->latest();

                if ($perPage > 0) {
                    $paginated = $query->paginate($perPage);
                    return [
                        'success' => true,
                        'status' => 'success',
                        'message' => 'Data service yang antri berhasil diambil.',
                        'data' => $paginated->items(),
                        'pagination' => [
                            'current_page' => $paginated->currentPage(),
                            'last_page' => $paginated->lastPage(),
                            'total' => $paginated->total(),
                            'per_page' => $paginated->perPage(),
                        ]
                    ];
                }

                $services = $query->get();

                return [
                    'success' => true,
                    'status' => 'success',
                    'message' => $services->isEmpty() ? 'Tidak ada data service yang antri.' : 'Data service yang antri berhasil diambil.',
                    'data' => $services,
                ];
            });

            return response()->json($result, 200);
        } catch (\Exception $e) {
            \Log::error('get_pending_services error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'status' => 'error',
                'message' => 'Gagal mengambil data service antri: ' . $e->getMessage(),
                'data' => [],
            ], 500);
        }
    }"""

if original_get_pending in text:
    text = text.replace(original_get_pending, new_get_pending)
    with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Admin/DashboardController.php', 'w', encoding='utf-8') as f:
        f.write(text)
    print("DashboardController updated successfully")
else:
    print("Original get_pending_services not found in DashboardController!")

with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Api/SparepartApiController.php', 'r', encoding='utf-8') as f:
    text = f.read()

original_indicators = """    public function getServiceIndicators(Request $request)
    {
        try {
            $startTime = microtime(true);

            // Tambah filter owner untuk keamanan data
            $services = modelServices::whereIn('status_services', ['Antri', 'Selesai'])
                                    ->where('kode_owner', $this->getThisUser()->id_upline)
                                    ->get();

            \Log::info('Services query completed', [
                'count' => $services->count(),
                'owner_id' => $this->getThisUser()->id_upline
            ]);

            if ($services->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'data' => [],
                    'message' => 'No services found'
                ]);
            }

            $indicators = [];
            $warrantyCheckTime = 0;
            $notesCheckTime = 0;

            foreach ($services as $service) {
                // Warranty check
                $warrantyStart = microtime(true);
                $hasWarranty = Garansi::where('kode_garansi', $service->kode_service)
                                     ->where('type_garansi', 'service')
                                     ->exists();
                $warrantyCheckTime += (microtime(true) - $warrantyStart);

                // Notes check
                $notesStart = microtime(true);
                $hasNotes = DetailCatatanService::where('kode_services', $service->id)->exists();
                $notesCheckTime += (microtime(true) - $notesStart);

                $indicators[$service->id] = [
                    'has_warranty' => $hasWarranty,
                    'has_notes' => $hasNotes
                ];
            }

            $totalTime = microtime(true) - $startTime;

            \Log::info('Service indicators completed', [
                'total_time' => round($totalTime * 1000, 2) . 'ms',
                'warranty_check_time' => round($warrantyCheckTime * 1000, 2) . 'ms',
                'notes_check_time' => round($notesCheckTime * 1000, 2) . 'ms',
                'services_processed' => count($indicators)
            ]);

            return response()->json([
                'success' => true,
                'data' => $indicators,
                'meta' => [
                    'processed' => count($indicators),
                    'execution_time_ms' => round($totalTime * 1000, 2)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getServiceIndicators', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil indikator service: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }"""

new_indicators = """    public function getServiceIndicators(Request $request)
    {
        try {
            $startTime = microtime(true);
            $uplineId = $this->getThisUser()->id_upline;
            
            // Menggunakan versioning cache
            $cacheVersion = \Illuminate\Support\Facades\Cache::rememberForever("service_cache_version_{$uplineId}", function() { return 1; });
            $cacheKey = "service_indicators_{$uplineId}_v{$cacheVersion}";

            $indicators = \Illuminate\Support\Facades\Cache::remember($cacheKey, 60, function() use ($uplineId) {
                $services = \App\Models\Sevices::whereIn('status_services', ['Antri', 'Selesai'])
                                        ->where('kode_owner', $uplineId)
                                        ->get(['id', 'kode_service']);

                if ($services->isEmpty()) {
                    return [];
                }

                $serviceIds = $services->pluck('id')->toArray();
                $kodeServices = $services->pluck('kode_service')->toArray();

                $warranties = \App\Models\Garansi::whereIn('kode_garansi', $kodeServices)
                                     ->where('type_garansi', 'service')
                                     ->pluck('kode_garansi')
                                     ->toArray();

                $notes = \App\Models\DetailCatatanService::whereIn('kode_services', $serviceIds)
                                             ->pluck('kode_services')
                                             ->toArray();

                $indicatorsData = [];
                foreach ($services as $service) {
                    $indicatorsData[$service->id] = [
                        'has_warranty' => in_array($service->kode_service, $warranties),
                        'has_notes' => in_array($service->id, $notes)
                    ];
                }

                return $indicatorsData;
            });

            $totalTime = microtime(true) - $startTime;

            \Log::info('Service indicators completed', [
                'total_time' => round($totalTime * 1000, 2) . 'ms',
                'services_processed' => count($indicators),
                'owner_id' => $uplineId
            ]);

            return response()->json([
                'success' => true,
                'data' => $indicators,
                'meta' => [
                    'processed' => count($indicators),
                    'execution_time_ms' => round($totalTime * 1000, 2)
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in getServiceIndicators', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil indikator service: ' . $e->getMessage(),
                'data' => []
            ], 500);
        }
    }"""

if original_indicators in text:
    text = text.replace(original_indicators, new_indicators)
    with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Api/SparepartApiController.php', 'w', encoding='utf-8') as f:
        f.write(text)
    print("SparepartApiController updated successfully")
else:
    print("Original getServiceIndicators not found in SparepartApiController!")
