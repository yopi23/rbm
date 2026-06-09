<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\ProductVariant;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class ProductApiController extends Controller
{
    /**
     * Helper to get the authenticated user's detail.
     */
    public function getThisUser()
    {
        return auth()->user()->userDetail;
    }

    /**
     * Display a listing of the products (spareparts).
     */
    public function index(Request $request)
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;

            $query = Sparepart::with(['kategori', 'supplier', 'variants.attributeValues.attribute'])
                ->where('kode_owner', $ownerId);

            // Filter search query
            if ($request->filled('q')) {
                $search = $request->input('q');
                $keywords = array_filter(explode(' ', strtolower($search)));
                
                $query->where(function ($q) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%')
                          ->orWhere('kode_sparepart', 'LIKE', '%' . $keyword . '%');
                    }
                });
            }

            // Filter category
            if ($request->filled('category_id')) {
                $query->where('kode_kategori', $request->input('category_id'));
            }


            // Filter supplier
            if ($request->filled('supplier_id')) {
                $query->where('kode_spl', $request->input('supplier_id'));
            }

            // Selalu hapus ActiveScope agar internal bisa melihat semua produk
            $query->withoutGlobalScope(\App\Scopes\ActiveScope::class);

            // Filter is_active hanya jika dikirimkan dari frontend
            if ($request->has('is_active')) {
                $query->where('spareparts.is_active', filter_var($request->input('is_active'), FILTER_VALIDATE_BOOLEAN));
            }

            // Filter by category visibility
            if ($request->has('category_is_active')) {
                $query->whereHas('kategori', function ($q) use ($request) {
                    $q->where('is_active', filter_var($request->input('category_is_active'), FILTER_VALIDATE_BOOLEAN));
                });
            }

            $limit = $request->input('limit', 15);
            $products = $query->latest('updated_at')->paginate($limit);

            // Format response
            $formattedProducts = collect($products->items())->map(function ($product) {
                return $this->formatProduct($product);
            });

            return response()->json([
                'success' => true,
                'data' => $formattedProducts,
                'pagination' => [
                    'total' => $products->total(),
                    'count' => $products->count(),
                    'per_page' => $products->perPage(),
                    'current_page' => $products->currentPage(),
                    'total_pages' => $products->lastPage(),
                    'has_more_pages' => $products->hasMorePages(),
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product API Index Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created product (sparepart).
     */
    public function store(Request $request)
    {
        // 1. Shift check
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        // 2. Validate request
        $validator = Validator::make($request->all(), [
            'nama_sparepart' => 'required|string|max:255',
            'kode_kategori' => 'required|integer|exists:kategori_spareparts,id',
            'desc_sparepart' => 'nullable|string',
            'stok_sparepart' => 'required|integer|min:0',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'required|numeric|min:0',
            'harga_ecer' => 'nullable|numeric|min:0',
            'harga_pasang' => 'required|numeric|min:0',
            'kode_spl' => 'nullable|integer|exists:suppliers,id',
            'is_active' => 'nullable|boolean',
            'photos' => 'nullable|array|max:5',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'foto_sparepart' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Combine photos array and legacy single foto_sparepart file field
        $photos = $request->file('photos') ?? [];
        if ($request->hasFile('foto_sparepart')) {
            // Unshift so the legacy single file goes first
            array_unshift($photos, $request->file('foto_sparepart'));
        }

        // Ensure max 5 photos total
        if (count($photos) > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 foto per produk.'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $photos) {
                $ownerId = $this->getThisUser()->id_upline;

                // Generate code
                $count = Sparepart::where('kode_owner', $ownerId)->latest()->get()->count();
                $kode_sparepart = 'SP' . date('Ymdhis') . $count;

                // Upload and save photos
                $uploadedPhotoPaths = [];
                foreach ($photos as $file) {
                    $filename = date('Ymdhis') . '_' . uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $file->move(public_path('uploads/'), $filename);
                    $uploadedPhotoPaths[] = $filename;
                }

                // Create Sparepart
                $product = Sparepart::create([
                    'kode_sparepart' => $kode_sparepart,
                    'kode_kategori' => $request->kode_kategori,
                    'nama_sparepart' => $request->nama_sparepart,
                    'desc_sparepart' => $request->desc_sparepart,
                    'stok_sparepart' => $request->stok_sparepart,
                    'harga_beli' => $request->harga_beli,
                    'harga_jual' => $request->harga_jual,
                    'harga_ecer' => $request->harga_ecer ?? $request->harga_jual,
                    'harga_pasang' => $request->harga_pasang,
                    'kode_owner' => $ownerId,
                    'kode_spl' => $request->kode_spl,
                    'is_active' => $request->input('is_active', 1),
                    'is_visible_on_web' => $request->input('is_visible_on_web', 1),
                    'foto_sparepart' => !empty($uploadedPhotoPaths) ? $uploadedPhotoPaths : '-',
                ]);

                // Create default ProductVariant
                ProductVariant::create([
                    'sparepart_id' => $product->id,
                    'sku' => $kode_sparepart,
                    'purchase_price' => $product->harga_beli,
                    'wholesale_price' => $product->harga_ecer ?? $product->harga_jual,
                    'retail_price' => $product->harga_jual,
                    'internal_price' => $product->harga_jual,
                    'stock' => $product->stok_sparepart,
                ]);

                $freshProduct = Sparepart::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                    ->with(['kategori', 'supplier', 'variants.attributeValues.attribute'])
                    ->find($product->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Produk berhasil ditambahkan.',
                    'data' => $this->formatProduct($freshProduct)
                ], 201);
            });

        } catch (\Exception $e) {
            Log::error('Product API Store Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display details of a single product (sparepart).
     */
    public function show($id)
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;
            $product = Sparepart::with(['kategori', 'supplier', 'variants.attributeValues.attribute'])
                ->withoutGlobalScope(\App\Scopes\ActiveScope::class)
                ->where('kode_owner', $ownerId)
                ->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $this->formatProduct($product)
            ]);

        } catch (\Exception $e) {
            Log::error('Product API Show Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan pada server.'
            ], 500);
        }
    }

    /**
     * Update a product (sparepart).
     */
    public function update(Request $request, $id)
    {
        // 1. Shift check
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $ownerId = $this->getThisUser()->id_upline;
        $product = Sparepart::withoutGlobalScope(\App\Scopes\ActiveScope::class)
            ->where('kode_owner', $ownerId)
            ->find($id);

        if (!$product) {
            return response()->json([
                'success' => false,
                'message' => 'Produk tidak ditemukan.'
            ], 404);
        }

        // 2. Validate request
        $validator = Validator::make($request->all(), [
            'nama_sparepart' => 'sometimes|required|string|max:255',
            'kode_kategori' => 'sometimes|required|integer|exists:kategori_spareparts,id',
            'desc_sparepart' => 'nullable|string',
            'stok_sparepart' => 'sometimes|required|integer|min:0',
            'harga_beli' => 'sometimes|required|numeric|min:0',
            'harga_jual' => 'sometimes|required|numeric|min:0',
            'harga_ecer' => 'nullable|numeric|min:0',
            'harga_pasang' => 'sometimes|required|numeric|min:0',
            'kode_spl' => 'nullable|integer|exists:suppliers,id',
            'is_active' => 'nullable|boolean',
            'delete_photo_ids' => 'nullable|array',
            'delete_photo_ids.*' => 'integer',
            'photos' => 'nullable|array',
            'photos.*' => 'image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
            'foto_sparepart' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()
            ], 422);
        }

        // Process deletion calculations
        $currentPhotos = $product->photos; // returns array of photo filenames from accessor
        $deletePhotoIds = $request->input('delete_photo_ids', []); // represents indices

        $deletedCount = 0;
        foreach ($deletePhotoIds as $idx) {
            if (isset($currentPhotos[$idx])) {
                $deletedCount++;
            }
        }

        $newPhotos = $request->file('photos') ?? [];
        if ($request->hasFile('foto_sparepart')) {
            array_unshift($newPhotos, $request->file('foto_sparepart'));
        }

        $finalCount = count($currentPhotos) - $deletedCount + count($newPhotos);

        if ($finalCount > 5) {
            return response()->json([
                'success' => false,
                'message' => 'Maksimal 5 foto per produk. (Saat ini: ' . count($currentPhotos) . ', dihapus: ' . $deletedCount . ', baru: ' . count($newPhotos) . ')'
            ], 422);
        }

        try {
            return DB::transaction(function () use ($request, $product, $deletePhotoIds, $newPhotos, $currentPhotos) {
                // Determine remaining photos and delete selected ones from storage
                $remainingPhotos = [];
                foreach ($currentPhotos as $index => $photoPath) {
                    if (in_array($index, $deletePhotoIds)) {
                        $path = public_path('uploads/' . $photoPath);
                        if (File::exists($path)) {
                            File::delete($path);
                        }
                    } else {
                        $remainingPhotos[] = $photoPath;
                    }
                }

                // Process new uploaded photos
                foreach ($newPhotos as $file) {
                    $filename = date('Ymdhis') . '_' . uniqid() . '_' . str_replace(' ', '_', $file->getClientOriginalName());
                    $file->move(public_path('uploads/'), $filename);
                    $remainingPhotos[] = $filename;
                }

                // Update product metadata details
                $product->update($request->only([
                    'kode_kategori',
                    'nama_sparepart',
                    'desc_sparepart',
                    'stok_sparepart',
                    'harga_beli',
                    'harga_jual',
                    'harga_ecer',
                    'harga_pasang',
                    'kode_spl',
                    'is_active',
                    'is_visible_on_web'
                ]));

                // Update photo column
                $product->update([
                    'foto_sparepart' => !empty($remainingPhotos) ? $remainingPhotos : '-'
                ]);

                // Also update the variant stock/prices
                $variant = $product->variants->first();
                if ($variant) {
                    $variant->update([
                        'purchase_price' => $product->harga_beli,
                        'wholesale_price' => $product->harga_ecer ?? $product->harga_jual,
                        'retail_price' => $product->harga_jual,
                        'internal_price' => $product->harga_jual,
                        'stock' => $product->stok_sparepart,
                    ]);
                }

                $freshProduct = Sparepart::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                    ->with(['kategori', 'supplier', 'variants.attributeValues.attribute'])
                    ->find($product->id);

                return response()->json([
                    'success' => true,
                    'message' => 'Produk berhasil diupdate.',
                    'data' => $this->formatProduct($freshProduct)
                ]);
            });

        } catch (\Exception $e) {
            Log::error('Product API Update Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified product (sparepart).
     */
    public function destroy($id)
    {
        // 1. Shift check
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $ownerId = $this->getThisUser()->id_upline;
            $product = Sparepart::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                ->where('kode_owner', $ownerId)
                ->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.'
                ], 404);
            }

            DB::transaction(function () use ($product) {
                // Delete photo files from storage
                $photos = $product->photos;
                foreach ($photos as $photoPath) {
                    $filePath = public_path('uploads/' . $photoPath);
                    if (File::exists($filePath)) {
                        File::delete($filePath);
                    }
                }

                // Delete related records
                $product->variants()->delete();
                $product->stockHistory()->delete();
                $product->stockNotifications()->delete();
                
                $hargaKhusus = \App\Models\HargaKhusus::where('id_sp', $product->id)->first();
                if ($hargaKhusus) {
                    $hargaKhusus->delete();
                }

                $product->delete();
            });

            return response()->json([
                'success' => true,
                'message' => 'Produk berhasil dihapus.'
            ]);

        } catch (\Exception $e) {
            Log::error('Product API Destroy Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus produk: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper to format the Sparepart model response.
     */
    private function formatProduct(Sparepart $product)
    {
        $mainPhoto = $product->foto_sparepart; // Returns the first item or '-' via accessor
        $mainPhotoUrl = null;
        if ($mainPhoto && $mainPhoto != '-') {
            $mainPhotoUrl = asset('uploads/' . $mainPhoto);
        }

        // Format photos list
        $photoPaths = $product->photos; // Returns the array of photo strings via accessor
        $photos = collect($photoPaths)->map(function ($photoPath, $index) {
            return [
                'id' => $index, // Index representing the photo position in the array
                'photo_path' => $photoPath,
                'url' => asset('uploads/' . $photoPath),
            ];
        })->values();

        // Format variants with attribute values
        $variants = $product->variants->map(function ($variant) use ($product) {
            $attributeString = $variant->attributeValues->map(fn ($av) => $av->value)->join(', ');
            $displayName = $product->nama_sparepart . ($attributeString ? ' - ' . $attributeString : '');

            return [
                'variant_id' => $variant->id,
                'sku' => $variant->sku,
                'display_name' => $displayName,
                'stock' => (int)$variant->stock,
                'prices' => [
                    'purchase' => (int)$variant->purchase_price,
                    'wholesale' => (int)$variant->wholesale_price,
                    'retail' => (int)$variant->retail_price,
                    'internal' => (int)$variant->internal_price,
                ],
                'attributes' => $variant->attributeValues->map(fn ($av) => [
                    'attribute_id' => $av->attribute->id ?? null,
                    'name' => $av->attribute->name ?? null,
                    'value' => $av->value,
                ]),
                'attribute_values' => $variant->attributeValues->map(fn ($av) => [
                    'id' => $av->id,
                    'value' => $av->value,
                    'attribute_id' => $av->attribute->id ?? null,
                    'attribute' => $av->attribute ? [
                        'id' => $av->attribute->id,
                        'name' => $av->attribute->name,
                    ] : null,
                ]),
            ];
        });

        return [
            'id' => $product->id,
            'kode_sparepart' => $product->kode_sparepart,
            'nama_sparepart' => $product->nama_sparepart,
            'desc_sparepart' => $product->desc_sparepart,
            'stok_sparepart' => (int)$product->stok_sparepart,
            'harga_beli' => (int)$product->harga_beli,
            'harga_jual' => (int)$product->harga_jual,
            'harga_ecer' => (int)$product->harga_ecer,
            'harga_pasang' => (int)$product->harga_pasang,
            'is_active' => (bool)$product->is_active,
            'is_visible_on_web' => (bool)$product->is_visible_on_web,
            'main_photo' => $mainPhoto,
            'main_photo_url' => $mainPhotoUrl,
            'photos' => $photos,
            'variants' => $variants,
            'kategori' => $product->kategori ? [
                'id' => $product->kategori->id,
                'nama_kategori' => $product->kategori->nama_kategori,
                'is_active' => (bool)$product->kategori->is_active,
            ] : null,
            'supplier' => $product->supplier ? [
                'id' => $product->supplier->id,
                'nama_supplier' => $product->supplier->nama_supplier,
            ] : null,
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }
    /**
     * Toggle visibility (is_active) of a product.
     */
    public function toggleVisibility(Request $request, $id)
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;
            $product = Sparepart::withoutGlobalScope(\App\Scopes\ActiveScope::class)
                ->where('kode_owner', $ownerId)
                ->find($id);

            if (!$product) {
                return response()->json([
                    'success' => false,
                    'message' => 'Produk tidak ditemukan.'
                ], 404);
            }

            $product->is_active = !$product->is_active;
            $product->save();

            return response()->json([
                'success' => true,
                'message' => $product->is_active ? 'Produk ditampilkan.' : 'Produk disembunyikan.',
                'data' => [
                    'id' => $product->id,
                    'nama_sparepart' => $product->nama_sparepart,
                    'is_active' => (bool)$product->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Product Toggle Visibility Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * List all sparepart categories for the owner.
     */
    public function listCategories()
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;
            $categories = \App\Models\KategoriSparepart::where('kode_owner', $ownerId)
                ->withCount(['spareparts' => function ($q) {
                    $q->withoutGlobalScope(\App\Scopes\ActiveScope::class);
                }])
                ->get()
                ->map(function ($cat) {
                    return [
                        'id' => $cat->id,
                        'nama_kategori' => $cat->nama_kategori,
                        'foto_kategori' => $cat->foto_kategori,
                        'foto_url' => ($cat->foto_kategori && $cat->foto_kategori != '-')
                            ? asset('uploads/' . $cat->foto_kategori)
                            : null,
                        'is_active' => (bool)$cat->is_active,
                        'spareparts_count' => $cat->spareparts_count,
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            Log::error('Category List Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Toggle visibility (is_active) of a category.
     */
    public function toggleCategoryVisibility($id)
    {
        try {
            $ownerId = $this->getThisUser()->id_upline;
            $category = \App\Models\KategoriSparepart::where('kode_owner', $ownerId)->find($id);

            if (!$category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan.'
                ], 404);
            }

            $category->is_active = !$category->is_active;
            $category->save();

            return response()->json([
                'success' => true,
                'message' => $category->is_active ? 'Kategori ditampilkan.' : 'Kategori disembunyikan.',
                'data' => [
                    'id' => $category->id,
                    'nama_kategori' => $category->nama_kategori,
                    'is_active' => (bool)$category->is_active,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Category Toggle Visibility Error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
