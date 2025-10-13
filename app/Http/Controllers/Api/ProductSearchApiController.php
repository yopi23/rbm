<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductSearchService; // <-- Import service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ProductSearchApiController extends Controller
{
    // Inject service melalui constructor
    protected $searchService;

    public function __construct(ProductSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    public function search(Request $request)
    {
        try {
            $validated = $request->validate([
                'q' => 'nullable|string|max:100',
                'category_id' => 'nullable|integer|exists:kategori_spareparts,id',
                'in_stock_only' => 'nullable|boolean',
                'limit' => 'nullable|integer|min:1|max:100',
            ]);

            $ownerId = Auth::user()->userDetail->id_upline;

            // Panggil method 'search' dari service
            $results = $this->searchService->search(
                $validated['q'] ?? null,
                $validated['category_id'] ?? null,
                filter_var($validated['in_stock_only'] ?? true, FILTER_VALIDATE_BOOLEAN),
                $validated['limit'] ?? 15,
                $ownerId
            );

            return response()->json([
                'status' => 'success',
                'data' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Product Search API Error: ' . $e->getMessage());
            return response()->json(['status' => 'error', 'message' => 'Terjadi kesalahan pada server.'], 500);
        }
    }
}
