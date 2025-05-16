<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HpData;
use Illuminate\Http\Request;

class HpApiController extends Controller
{
    /**
     * Mencari data HP berdasarkan kata kunci
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function search(Request $request)
    {
        // Validasi request
        $request->validate([
            'keyword' => 'nullable|string|min:1',
            'type' => 'nullable|string|min:1',
            'brand_id' => 'nullable|integer|exists:brands,id',
            'screen_size_id' => 'nullable|integer|exists:screen_sizes,id',
            'camera_position_id' => 'nullable|integer|exists:camera_positions,id',
            'camera_group' => 'nullable|string'
        ]);

        $keyword = $request->input('keyword');
        $type = $request->input('type');

        // Buat query builder dengan eager loading untuk relasi
        $query = HpData::with(['brand', 'screenSize', 'cameraPosition']);

        // Terapkan filter berdasarkan parameter yang diberikan
        if ($keyword) {
            $query->where(function($q) use ($keyword) {
                $q->where('type', 'LIKE', "%{$keyword}%")
                  ->orWhereHas('brand', function($brandQuery) use ($keyword) {
                      $brandQuery->where('name', 'LIKE', "%{$keyword}%");
                  });
            });
        }

        // Filter khusus berdasarkan type yang lebih spesifik
        if ($type) {
            $query->where('type', 'LIKE', "%{$type}%");
        }

        if ($request->has('brand_id')) {
            $query->where('brand_id', $request->input('brand_id'));
        }

        if ($request->has('screen_size_id')) {
            $query->where('screen_size_id', $request->input('screen_size_id'));
        }

        if ($request->has('camera_position_id')) {
            $query->where('camera_position_id', $request->input('camera_position_id'));
        }

        if ($request->has('camera_group')) {
            $cameraGroup = $request->input('camera_group');
            $query->whereHas('cameraPosition', function($q) use ($cameraGroup) {
                $q->where('group', $cameraGroup);
            });
        }

        // Dapatkan hasil query
        $results = $query->get();

        // Format data hasil pencarian
        $formattedResults = $results->map(function($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'brand' => [
                    'id' => $item->brand->id,
                    'name' => $item->brand->name
                ],
                'screen_size' => [
                    'id' => $item->screenSize->id,
                    'size' => $item->screenSize->size
                ],
                'camera_position' => [
                    'id' => $item->cameraPosition->id,
                    'position' => $item->cameraPosition->position,
                    'group' => $item->cameraPosition->group
                ],
                // Cari HP dengan ukuran layar DAN posisi kamera yang sama persis
                'similar_models' => $this->getSimilarHpModels($item)
            ];
        });

        return response()->json([
            'success' => true,
            'total' => $formattedResults->count(),
            'data' => $formattedResults
        ]);
    }

    /**
     * Cari HP dengan ukuran layar dan posisi kamera yang sama persis
     * (kecuali yang sama persis dengan item asal)
     * @param HpData $item
     * @return array
     */
    private function getSimilarHpModels($item)
    {
        $similar = HpData::where('screen_size_id', $item->screen_size_id)
            ->where('camera_position_id', $item->camera_position_id)
            ->where('id', '!=', $item->id)
            ->with(['brand'])
            ->limit(10)
            ->get();

        return $similar->map(function($hp) {
            return [
                'id' => $hp->id,
                'type' => $hp->type,
                'brand' => $hp->brand->name,
                'screen_size' => $hp->screenSize->size,
                'camera_position' => $hp->cameraPosition->position
            ];
        })->toArray();
    }

    /**
     * Mendapatkan detail HP berdasarkan ID
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function detail($id)
    {
        try {
            $hp = HpData::with(['brand', 'screenSize', 'cameraPosition'])
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $hp->id,
                    'type' => $hp->type,
                    'brand' => [
                        'id' => $hp->brand->id,
                        'name' => $hp->brand->name
                    ],
                    'screen_size' => [
                        'id' => $hp->screenSize->id,
                        'size' => $hp->screenSize->size
                    ],
                    'camera_position' => [
                        'id' => $hp->cameraPosition->id,
                        'position' => $hp->cameraPosition->position,
                        'group' => $hp->cameraPosition->group
                    ],
                    // HP lain dengan ukuran layar DAN posisi kamera yang sama persis
                    'similar_models' => $this->getSimilarHpModels($hp)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data HP tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Mendapatkan daftar filter untuk pencarian
     * @return \Illuminate\Http\JsonResponse
     */
    public function filters()
    {
        $brands = \App\Models\Brand::orderBy('name')->get(['id', 'name']);
        $screenSizes = \App\Models\ScreenSize::orderBy('size')->get(['id', 'size']);

        // Dapatkan posisi kamera dan kelompokkan berdasarkan group
        $cameraPositions = \App\Models\CameraPosition::orderBy('group')
                            ->orderBy('position')
                            ->get(['id', 'position', 'group']);

        $cameraGroups = $cameraPositions->pluck('group')->unique()->values();

        return response()->json([
            'success' => true,
            'data' => [
                'brands' => $brands,
                'screen_sizes' => $screenSizes,
                'camera_positions' => $cameraPositions,
                'camera_groups' => $cameraGroups
            ]
        ]);
    }

    /**
     * Mencari data HP berdasarkan tipe (endpoint khusus)
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchByType(Request $request)
    {
        $request->validate([
            'type' => 'required|string|min:1'
        ]);

        $type = $request->input('type');

        // Cari data HP dengan exact match dulu
        $exactMatch = HpData::where('type', $type)
                       ->with(['brand', 'screenSize', 'cameraPosition'])
                       ->first();

        if ($exactMatch) {
            // Jika ditemukan exact match, kembalikan dengan semua informasi persamaan
            return response()->json([
                'success' => true,
                'exact_match' => true,
                'data' => [
                    'id' => $exactMatch->id,
                    'type' => $exactMatch->type,
                    'brand' => [
                        'id' => $exactMatch->brand->id,
                        'name' => $exactMatch->brand->name
                    ],
                    'screen_size' => [
                        'id' => $exactMatch->screenSize->id,
                        'size' => $exactMatch->screenSize->size
                    ],
                    'camera_position' => [
                        'id' => $exactMatch->cameraPosition->id,
                        'position' => $exactMatch->cameraPosition->position,
                        'group' => $exactMatch->cameraPosition->group
                    ],
                    // HP lain dengan ukuran layar DAN posisi kamera yang sama persis
                    'similar_models' => $this->getSimilarHpModels($exactMatch)
                ]
            ]);
        }

        // Jika tidak ada exact match, cari dengan partial match
        $partialMatches = HpData::where('type', 'LIKE', "%{$type}%")
                            ->with(['brand', 'screenSize', 'cameraPosition'])
                            ->get();

        if ($partialMatches->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ditemukan data HP dengan tipe: ' . $type,
                'data' => []
            ]);
        }

        // Format data hasil pencarian
        $formattedResults = $partialMatches->map(function($item) {
            return [
                'id' => $item->id,
                'type' => $item->type,
                'brand' => [
                    'id' => $item->brand->id,
                    'name' => $item->brand->name
                ],
                'screen_size' => [
                    'id' => $item->screenSize->id,
                    'size' => $item->screenSize->size
                ],
                'camera_position' => [
                    'id' => $item->cameraPosition->id,
                    'position' => $item->cameraPosition->position,
                    'group' => $item->cameraPosition->group
                ],
                // HP lain dengan ukuran layar DAN posisi kamera yang sama persis
                'similar_models' => $this->getSimilarHpModels($item)
            ];
        });

        return response()->json([
            'success' => true,
            'exact_match' => false,
            'total' => $formattedResults->count(),
            'data' => $formattedResults
        ]);
    }
}
