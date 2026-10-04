import re

with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Api/SparepartApiController.php', 'r', encoding='utf-8') as f:
    text = f.read()

pattern = re.compile(r'public function getServiceIndicators.*?500\);\s*\}', re.DOTALL)

new_indicators = r"""public function getServiceIndicators(Request $request)
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

if pattern.search(text):
    text = pattern.sub(new_indicators.replace('\\', '\\\\'), text, count=1)
    with open('C:/Users/yopi/Documents/project/rbm/app/Http/Controllers/Api/SparepartApiController.php', 'w', encoding='utf-8') as f:
        f.write(text)
    print("SparepartApiController updated successfully")
else:
    print("Regex did not match!")
