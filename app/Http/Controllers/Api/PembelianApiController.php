<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Sparepart;
use App\Models\Supplier;
use App\Models\ProductVariant;
use App\Models\KategoriSparepart;
use App\Models\SubKategoriSparepart;
use App\Services\PriceCalculationService;
use App\Traits\ManajemenKasTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Hutang;
use App\Models\Shift;
use App\Scopes\ActiveScope;

class PembelianApiController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId(): int
    {
        $user = Auth::user();
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    /**
     * GET /api/pembelian
     * Mendapatkan daftar semua pembelian
     */
    public function index(Request $request)
    {
        try {
            $query = Pembelian::where('kode_owner', $this->getOwnerId());

            if ($request->filled('search')) {
                $search = trim($request->search);
                $query->where(function ($q) use ($search) {
                    $q->where('kode_pembelian', 'like', "%{$search}%")
                        ->orWhere('supplier', 'like', "%{$search}%")
                        ->orWhere('keterangan', 'like', "%{$search}%")
                        ->orWhereHas('detailPembelians', function ($dq) use ($search) {
                            $dq->where('nama_item', 'like', "%{$search}%")
                                ->orWhereHas('sparepart', function ($sq) use ($search) {
                                    $sq->where('nama_sparepart', 'like', "%{$search}%");
                                });
                        });
                });
            }

            $pembelians = $query->with(['detailPembelians' => function ($dq) {
                    $dq->select('id', 'pembelian_id', 'nama_item', 'jumlah', 'harga_beli', 'total');
                }])
                ->orderBy('tanggal_pembelian', 'desc')
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($pembelian) {
                    return [
                        'id' => $pembelian->id,
                        'kode_pembelian' => $pembelian->kode_pembelian,
                        'tanggal_pembelian' => $pembelian->tanggal_pembelian,
                        'supplier' => $pembelian->supplier,
                        'total_harga' => $pembelian->total_harga,
                        'status' => $pembelian->status,
                        'keterangan' => $pembelian->keterangan,
                        'created_at' => $pembelian->created_at ? $pembelian->created_at->toIso8601String() : null,
                        'items_summary' => $pembelian->detailPembelians->map(function ($item) {
                            return [
                                'id' => $item->id,
                                'nama_item' => $item->nama_item,
                                'jumlah' => $item->jumlah,
                                'harga_beli' => $item->harga_beli,
                                'total' => $item->total,
                            ];
                        })->values(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pembelians,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pembelian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pembelian
     * Membuat pembelian baru (draft)
     */
    public function store()
    {
        try {
            // Generate kode pembelian
            $lastPembelian = Pembelian::orderBy('id', 'desc')->first();
            $kode_pembelian = 'PB-' . date('Ymd') . '-';

            if ($lastPembelian) {
                $lastNumber = intval(Str::substr($lastPembelian->kode_pembelian, -3));
                $kode_pembelian .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            }
            else {
                $kode_pembelian .= '001';
            }

            // Get Active Shift ID
            $activeShift = Shift::getActiveShift(auth()->user()->id);
            if (!$activeShift) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
                ], 403);
            }
            $shiftId = $activeShift->id;

            $pembelian = Pembelian::create([
                'kode_pembelian' => $kode_pembelian,
                'tanggal_pembelian' => date('Y-m-d'),
                'total_harga' => 0,
                'kode_owner' => $this->getOwnerId(),
                'status' => 'draft',
                'shift_id' => $shiftId,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pembelian draft berhasil dibuat',
                'data' => [
                    'id' => $pembelian->id,
                    'kode_pembelian' => $pembelian->kode_pembelian,
                    'tanggal_pembelian' => $pembelian->tanggal_pembelian,
                    'total_harga' => $pembelian->total_harga,
                    'status' => $pembelian->status,
                ],
            ], 201);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pembelian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pembelian/{id}
     * Mendapatkan detail pembelian beserta items
     */
    public function show($id)
    {
        try {
            $pembelian = Pembelian::where('kode_owner', $this->getOwnerId())
                ->findOrFail($id);

            $rawDetails = DetailPembelian::with([
                'kategori',
                'productVariant.sparepart' => function ($q) {
                    $q->withoutGlobalScopes()->with('kategori');
                },
                'productVariant.attributeValues.attribute'
            ])
                ->where('pembelian_id', $id)
                ->get();

            // Kumpulkan seluruh ID attribute_value dari JSON attributes untuk di-resolve sekaligus
            $allAttrValueIds = [];
            foreach ($rawDetails as $d) {
                $decoded = is_string($d->attributes)
                    ? json_decode($d->attributes, true)
                    : (is_array($d->attributes) ? $d->attributes : []);
                if (is_array($decoded)) {
                    foreach ($decoded as $valId) {
                        if (!empty($valId)) {
                            $allAttrValueIds[] = (int) $valId;
                        }
                    }
                }
            }
            $attrValuesMap = !empty($allAttrValueIds)
                ? \App\Models\AttributeValue::with('attribute')
                    ->whereIn('id', array_unique($allAttrValueIds))
                    ->get()
                    ->keyBy('id')
                : collect();

            $ownerCategories = KategoriSparepart::with('attributes.values')
                ->where('kode_owner', $this->getOwnerId())
                ->get()
                ->keyBy('id');

            $details = $rawDetails->map(function ($detail) use ($attrValuesMap, $ownerCategories, $pembelian) {
                $variants = [];

                // Prioritas 1: Ambil dari kolom JSON attributes (berlaku untuk Item Baru & Restock yang diedit)
                $decodedAttrs = is_string($detail->attributes)
                    ? json_decode($detail->attributes, true)
                    : (is_array($detail->attributes) ? $detail->attributes : []);

                if (is_array($decodedAttrs) && !empty($decodedAttrs)) {
                    foreach ($decodedAttrs as $attrId => $valId) {
                        $av = $attrValuesMap->get((int) $valId);
                        if ($av) {
                            $variants[] = [
                                'attribute_id' => $av->attribute_id,
                                'attribute_value_id' => $av->id,
                                'attribute_name' => $av->attribute->name ?? '',
                                'value' => $av->value,
                            ];
                        }
                    }
                }

                // Prioritas 2: Fallback ke relasi productVariant.attributeValues
                if (empty($variants) && $detail->productVariant && $detail->productVariant->attributeValues) {
                    foreach ($detail->productVariant->attributeValues as $attrValue) {
                        $variants[] = [
                            'attribute_id' => $attrValue->attribute_id,
                            'attribute_value_id' => $attrValue->id,
                            'attribute_name' => $attrValue->attribute->name ?? '',
                            'value' => $attrValue->value,
                        ];
                    }
                }

                $itemKategori = $detail->item_kategori
                    ?? $detail->productVariant?->sparepart?->kode_kategori
                    ?? null;

                $lowerItemName = strtolower($detail->nama_item ?? '');

                // Jika kategori belum terisi atau default namun nama barang diawali/mengandung nama kategori lain (misal "LCD")
                if ($ownerCategories->isNotEmpty() && !empty($lowerItemName)) {
                    $matchedCat = null;
                    foreach ($ownerCategories as $cat) {
                        $catNameLower = strtolower(trim($cat->nama_kategori ?? ''));
                        if ($catNameLower !== '' && str_contains($lowerItemName, $catNameLower)) {
                            $matchedCat = $cat;
                            break;
                        }
                    }
                    if ($matchedCat && (empty($itemKategori) || ($detail->is_new_item && empty($variants)))) {
                        $itemKategori = $matchedCat->id;
                    }
                }

                $namaKategori = $detail->kategori->nama_kategori
                    ?? $detail->productVariant?->sparepart?->kategori?->nama_kategori
                    ?? ($itemKategori && $ownerCategories->has($itemKategori) ? $ownerCategories->get($itemKategori)->nama_kategori : null);

                // Prioritas 3: Jika masih belum ada atribut (misal hasil scan nota yang belum ter-set atributnya), deteksi otomatis dari nama barang
                if (empty($variants) && $itemKategori && $ownerCategories->has($itemKategori) && !empty($lowerItemName)) {
                    $catObj = $ownerCategories->get($itemKategori);
                    $autoAttrs = [];
                    foreach ($catObj->attributes ?? [] as $attr) {
                        $sortedVals = collect($attr->values ?? [])->sortByDesc(fn($v) => strlen($v->value ?? ''));
                        foreach ($sortedVals as $valObj) {
                            $valText = strtolower(trim($valObj->value ?? ''));
                            if ($valText !== '' && str_contains($lowerItemName, $valText)) {
                                $autoAttrs[(string) $attr->id] = (string) $valObj->id;
                                $variants[] = [
                                    'attribute_id' => $attr->id,
                                    'attribute_value_id' => $valObj->id,
                                    'attribute_name' => $attr->name ?? '',
                                    'value' => $valObj->value,
                                ];
                                break;
                            }
                        }
                    }
                    if (!empty($autoAttrs) && $pembelian->status === 'draft') {
                        $detail->item_kategori = $itemKategori;
                        $detail->attributes = json_encode($autoAttrs);
                        $detail->save();
                    }
                }

                return [
                    'id' => $detail->id,
                    'nama_item' => $detail->nama_item,
                    'jumlah' => $detail->jumlah,
                    'harga_beli' => $detail->harga_beli,
                    'total' => $detail->total,
                    'is_new_item' => $detail->is_new_item,
                    'product_variant_id' => $detail->product_variant_id,
                    'sparepart_id' => $detail->sparepart_id,
                    'item_kategori' => $itemKategori,
                    'nama_kategori' => $namaKategori,
                    'attributes' => $detail->attributes,
                    'variants' => $variants,
                    'product_variant' => $detail->productVariant,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $pembelian->id,
                    'kode_pembelian' => $pembelian->kode_pembelian,
                    'tanggal_pembelian' => $pembelian->tanggal_pembelian,
                    'supplier' => $pembelian->supplier,
                    'total_harga' => $pembelian->total_harga,
                    'status' => $pembelian->status,
                    'keterangan' => $pembelian->keterangan,
                    'details' => $details,
                ],
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pembelian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pembelian/{id}
     * Update informasi pembelian (tanggal, supplier, keterangan)
     */
    public function update(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validated = $request->validate([
            'tanggal_pembelian' => 'sometimes|date',
            'supplier' => 'sometimes|nullable|string|max:255',
            'keterangan' => 'sometimes|nullable|string',
        ]);

        try {
            $pembelian = Pembelian::where('kode_owner', $this->getOwnerId())
                ->findOrFail($id);

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.',
                ], 403);
            }

            $pembelian->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil diperbarui',
                'data' => $pembelian,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pembelian: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pembelian/{id}/items
     * Menambah item ke pembelian
     */
    public function addItem(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validated = $request->validate([
            'nama_item' => 'required|string',
            'jumlah' => 'required|integer|min:1',
            'harga_beli' => 'required|numeric|min:0',
            'is_new_item' => 'required|boolean',
            'sparepart_id' => 'nullable|exists:spareparts,id',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'attributes' => 'nullable|array',
            'item_kategori' => 'nullable|exists:kategori_spareparts,id',

        ]);


        DB::beginTransaction();

        try {
            $pembelian = Pembelian::where('kode_owner', $this->getOwnerId())
                ->findOrFail($id);

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.',
                ], 403);
            }

            $detailData = [
                'pembelian_id' => $pembelian->id,
                'nama_item' => $validated['nama_item'],
                'jumlah' => $validated['jumlah'],
                'harga_beli' => $validated['harga_beli'],
                'total' => $validated['jumlah'] * $validated['harga_beli'],
                'is_new_item' => $validated['is_new_item'],
            ];

            if ($validated['is_new_item']) {
                $detailData['item_kategori'] = $validated['item_kategori'] ?? null;
                $detailData['attributes'] = json_encode($validated['attributes'] ?? []);
            }
            else {
                $detailData['product_variant_id'] = $validated['product_variant_id'] ?? null;
                $variant = !empty($validated['product_variant_id'])
                    ? ProductVariant::with(['sparepart' => fn($q) => $q->withoutGlobalScopes(), 'attributeValues'])->find($validated['product_variant_id'])
                    : null;

                $resolvedKategori = $validated['item_kategori'] ?? $variant?->sparepart?->kode_kategori;
                $resolvedAttrs = !empty($validated['attributes'])
                    ? $validated['attributes']
                    : ($variant ? $variant->attributeValues->pluck('id', 'attribute_id')->map(fn($v) => (string) $v)->toArray() : []);

                $detailData['item_kategori'] = $resolvedKategori;
                $detailData['attributes'] = json_encode($resolvedAttrs);

                if ($variant) {
                    $detailData['sparepart_id'] = $variant->sparepart_id;
                }
            }

            $detail = DetailPembelian::create($detailData);

            $pembelian->total_harga += $detailData['total'];
            $pembelian->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan',
                'data' => $detail,
            ], 201);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pembelian/{id}/items/bulk
     * Menambah banyak item sekaligus ke pembelian (misal dari hasil scan nota AI)
     */
    public function bulkAddItems(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validated = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.nama_item' => 'required|string',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_beli' => 'required|numeric|min:0',
            'items.*.is_new_item' => 'required|boolean',
            'items.*.sparepart_id' => 'nullable|exists:spareparts,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.attributes' => 'nullable|array',
            'items.*.item_kategori' => 'nullable|exists:kategori_spareparts,id',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            $pembelian = Pembelian::where('kode_owner', $this->getOwnerId())
                ->findOrFail($id);

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.',
                ], 403);
            }

            $addedItems = [];
            $additionalTotal = 0;

            foreach ($validated['items'] as $itemData) {
                $detailData = [
                    'pembelian_id' => $pembelian->id,
                    'nama_item' => $itemData['nama_item'],
                    'jumlah' => $itemData['jumlah'],
                    'harga_beli' => $itemData['harga_beli'],
                    'total' => $itemData['jumlah'] * $itemData['harga_beli'],
                    'is_new_item' => $itemData['is_new_item'],
                ];

                if ($itemData['is_new_item']) {
                    $detailData['item_kategori'] = $itemData['item_kategori'] ?? null;
                    $detailData['attributes'] = json_encode($itemData['attributes'] ?? []);
                } else {
                    $detailData['product_variant_id'] = $itemData['product_variant_id'] ?? null;
                    $variant = !empty($itemData['product_variant_id'])
                        ? ProductVariant::with(['sparepart' => fn($q) => $q->withoutGlobalScopes(), 'attributeValues'])->find($itemData['product_variant_id'])
                        : null;

                    $resolvedKategori = $itemData['item_kategori'] ?? $variant?->sparepart?->kode_kategori;
                    $resolvedAttrs = !empty($itemData['attributes'])
                        ? $itemData['attributes']
                        : ($variant ? $variant->attributeValues->pluck('id', 'attribute_id')->map(fn($v) => (string) $v)->toArray() : []);

                    $detailData['item_kategori'] = $resolvedKategori;
                    $detailData['attributes'] = json_encode($resolvedAttrs);

                    if ($variant) {
                        $detailData['sparepart_id'] = $variant->sparepart_id;
                    }
                }

                $detail = DetailPembelian::create($detailData);
                $addedItems[] = $detail;
                $additionalTotal += $detailData['total'];
            }

            $pembelian->total_harga += $additionalTotal;
            if (!empty($validated['keterangan']) && empty($pembelian->keterangan)) {
                $pembelian->keterangan = $validated['keterangan'];
            }
            $pembelian->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($addedItems) . ' item berhasil ditambahkan ke pembelian.',
                'data' => $addedItems,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah item secara massal: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pembelian/items/{detailId}
     * Update item pembelian
     */
    public function updateItem(Request $request, $detailId)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validated = $request->validate([
            'nama_item' => 'required|string|max:8000',
            'jumlah' => 'required|integer|min:1',
            'harga_beli' => 'required|numeric|min:0',
            'is_new_item' => 'sometimes|boolean',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'attributes' => 'nullable|array',
            'item_kategori' => 'nullable|exists:kategori_spareparts,id',
        ]);

        DB::beginTransaction();

        try {
            $detail = DetailPembelian::with('pembelian')->findOrFail($detailId);
            $pembelian = $detail->pembelian;

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.',
                ], 403);
            }

            $oldTotal = $detail->total;
            $isNewItem = array_key_exists('is_new_item', $validated)
                ? (bool) $validated['is_new_item']
                : (bool) $detail->is_new_item;

            $updateData = [
                'nama_item' => $validated['nama_item'],
                'jumlah' => $validated['jumlah'],
                'harga_beli' => $validated['harga_beli'],
                'total' => $validated['jumlah'] * $validated['harga_beli'],
                'is_new_item' => $isNewItem,
                'item_kategori' => $validated['item_kategori'] ?? $detail->item_kategori,
                'attributes' => json_encode($validated['attributes'] ?? []),
            ];

            if (!$isNewItem) {
                $variantId = $validated['product_variant_id'] ?? $detail->product_variant_id;
                $updateData['product_variant_id'] = $variantId;
                $variant = $variantId
                    ? ProductVariant::with(['sparepart' => fn($q) => $q->withoutGlobalScopes(), 'attributeValues'])->find($variantId)
                    : null;
                if ($variant) {
                    $updateData['sparepart_id'] = $variant->sparepart_id;
                    if (empty($updateData['item_kategori'])) {
                        $updateData['item_kategori'] = $variant->sparepart?->kode_kategori;
                    }
                    if (empty($validated['attributes'])) {
                        $updateData['attributes'] = json_encode(
                            $variant->attributeValues->pluck('id', 'attribute_id')->map(fn($v) => (string) $v)->toArray()
                        );
                    }
                }
            } else {
                $updateData['product_variant_id'] = null;
                $updateData['sparepart_id'] = null;
            }

            $detail->update($updateData);

            $pembelian->total_harga = ($pembelian->total_harga - $oldTotal) + $updateData['total'];
            $pembelian->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperbarui',
                'data' => $detail,
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * DELETE /api/pembelian/items/{id}
     * Hapus item dari pembelian
     */
    public function removeItem($id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $detail = DetailPembelian::findOrFail($id);
            $pembelian = Pembelian::findOrFail($detail->pembelian_id);

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.',
                ], 403);
            }

            $pembelian->total_harga -= $detail->total;
            $pembelian->save();

            $detail->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus',
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * POST /api/pembelian/{id}/finalize
     * Selesaikan pembelian dan update stok
     */
    public function finalize(Request $request, $id, PriceCalculationService $priceCalculator)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validated = $request->validate([
            'metode_pembayaran' => 'required|in:Lunas,Hutang',
            'tgl_jatuh_tempo' => 'required_if:metode_pembayaran,Hutang|nullable|date',
            'supplier' => 'required|exists:suppliers,id',
        ]);

        DB::beginTransaction();

        try {
            $pembelian = Pembelian::with('detailPembelians')
                ->where('kode_owner', $this->getOwnerId())
                ->findOrFail($id);

            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian ini sudah diselesaikan sebelumnya.',
                ], 400);
            }

            if ($pembelian->detailPembelians->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian tidak dapat diselesaikan karena tidak ada item.',
                ], 400);
            }

            $supplier = Supplier::findOrFail($validated['supplier']);

            foreach ($pembelian->detailPembelians as $detail) {
                if ($detail->is_new_item) {
                    // Proses item baru
                    $attributeValueIds = array_filter(json_decode($detail->attributes, true) ?: []);

                    $calculatedPrices = $priceCalculator->calculate(
                        $detail->harga_beli,
                        $detail->item_kategori,
                        $attributeValueIds
                    );

                    if (is_null($calculatedPrices)) {
                        throw new \Exception('Tidak ada aturan harga yang valid untuk item: "' . $detail->nama_item . '"');
                    }

                    $ownerId = $this->getOwnerId();
                    $kodeSparepartBaru = 'SP' . date('YmdHis') . rand(100, 999);
                    $sparepart = Sparepart::withoutGlobalScopes()->firstOrCreate(
                    [
                        'nama_sparepart' => $detail->nama_item,
                        'kode_kategori' => $detail->item_kategori,
                        'kode_owner' => $ownerId,
                    ],
                    [
                        'kode_sparepart' => $kodeSparepartBaru,
                        'kode_spl' => $validated['supplier'],
                        'cabang_id' => auth()->user()->cabang_id,
                        'is_active' => 1,
                        'foto_sparepart' => '-',
                        'desc_sparepart' => null,
                        'stok_sparepart' => 0,
                        'harga_beli' => $detail->harga_beli,
                        'harga_jual' => $calculatedPrices['internal_price'],
                        'harga_ecer' => $calculatedPrices['wholesale_price'],
                        'harga_pasang' => $calculatedPrices['default_service_fee'] ?? 0,
                        'kode_sub_kategori' => null,
                        'stock_asli' => null,
                    ]
                    );

                    $variant = $sparepart->variants()->create([
                        'sku' => $sparepart->kode_sparepart,
                        'purchase_price' => $detail->harga_beli,
                        'stock' => 0,
                        'wholesale_price' => $calculatedPrices['wholesale_price'],
                        'retail_price' => $calculatedPrices['retail_price'],
                        'internal_price' => $calculatedPrices['internal_price'],
                    ]);

                    if (!empty($attributeValueIds)) {
                        $variant->attributeValues()->sync(array_values($attributeValueIds));
                    }

                    $detail->product_variant_id = $variant->id;
                    $detail->save();

                    // Use logStockChange for centralized stock management
                    $sparepart->logStockChange(
                        $detail->jumlah,
                        'pembelian',
                        $pembelian->kode_pembelian,
                        'Pembelian Item Baru: ' . $detail->nama_item,
                        auth()->user()->id,
                        $variant->id
                    );

                }
                else {
                    // Proses restock
                    $variant = ProductVariant::with('attributeValues')->find($detail->product_variant_id);

                    if ($variant) {
                        $sparepart = Sparepart::withoutGlobalScopes()->find($detail->sparepart_id ?: $variant->sparepart_id);

                        if (!$sparepart) {
                            throw new \Exception('Data sparepart tidak ditemukan untuk item restock: "' . $detail->nama_item . '"');
                        }

                        $categoryId = $detail->item_kategori ?: $sparepart->kode_kategori;
                        $attributeValueIds = array_filter(json_decode($detail->attributes, true) ?: []);
                        if (empty($attributeValueIds)) {
                            $attributeValueIds = $variant->attributeValues->pluck('id')->toArray();
                        }

                        $calculatedPrices = $priceCalculator->calculate(
                            $detail->harga_beli,
                            $categoryId,
                            $attributeValueIds
                        );

                        if (is_null($calculatedPrices)) {
                            throw new \Exception('Tidak ada aturan harga yang valid untuk item restock: "' . $detail->nama_item . '"');
                        }

                        // Weighted average cost
                        $old_stock = $variant->stock;
                        $current_average_cost = $variant->purchase_price;
                        $new_stock_quantity = $detail->jumlah;
                        $new_stock_cost = $detail->harga_beli;

                        $total_old_value = $old_stock * $current_average_cost;
                        $total_new_value = $new_stock_quantity * $new_stock_cost;
                        $new_total_stock = $old_stock + $new_stock_quantity;

                        $new_average_cost = ($new_total_stock > 0)
                            ? ($total_old_value + $total_new_value) / $new_total_stock
                            : $new_stock_cost;

                        // Update prices
                        $variant->purchase_price = $new_average_cost;
                        $variant->wholesale_price = $calculatedPrices['wholesale_price'];
                        $variant->retail_price = $calculatedPrices['retail_price'];
                        $variant->internal_price = $calculatedPrices['internal_price'];
                        // $variant->stock will be updated by logStockChange

                        if (!empty($attributeValueIds)) {
                            $variant->attributeValues()->sync(array_values($attributeValueIds));
                        }

                        // Update nama sparepart jika ada update nama baru dari nota/form
                        $trimmedNewName = trim($detail->nama_item ?? '');
                        if (!empty($trimmedNewName) && $sparepart->nama_sparepart !== $trimmedNewName) {
                            $sparepart->nama_sparepart = $trimmedNewName;
                        }
                        if (!empty($detail->item_kategori)) {
                            $sparepart->kode_kategori = $detail->item_kategori;
                        }

                        $sparepart->harga_beli = $new_stock_cost;
                        $sparepart->harga_jual = $calculatedPrices['internal_price'];
                        $sparepart->harga_ecer = $calculatedPrices['wholesale_price'];
                        $sparepart->harga_pasang = $calculatedPrices['default_service_fee'] ?? 0;
                        // $sparepart->stok_sparepart will be updated by logStockChange



                        // Use logStockChange for centralized stock management
                        $sparepart->logStockChange(
                            $detail->jumlah,
                            'pembelian',
                            $pembelian->kode_pembelian,
                            'Restock Item: ' . $detail->nama_item,
                            auth()->user()->id,
                            $variant->id
                        );
                    }
                }
            }

            // Update pembelian
            $pembelian->supplier = $supplier->nama_supplier;
            $pembelian->status = 'selesai';
            $pembelian->metode_pembayaran = $validated['metode_pembayaran'];

            // Update Shift ID to current active shift
            $activeShift = Shift::getActiveShift(auth()->user()->id);
            if ($activeShift) {
                $pembelian->shift_id = $activeShift->id;
            }

            if ($validated['metode_pembayaran'] == 'Hutang') {
                $pembelian->status_pembayaran = 'Belum Lunas';
                $pembelian->tgl_jatuh_tempo = $validated['tgl_jatuh_tempo'];

                Hutang::create([
                    'kode_supplier' => $supplier->id,
                    'kode_owner' => $this->getOwnerId(),
                    'kode_nota' => $pembelian->kode_pembelian,
                    'total_hutang' => $pembelian->total_harga,
                    'tgl_jatuh_tempo' => $validated['tgl_jatuh_tempo'],
                    'status' => 'Belum Lunas',
                ]);
            }
            else {
                $pembelian->status_pembayaran = 'Lunas';

                $this->catatKas(
                    $pembelian,
                    0,
                    $pembelian->total_harga,
                    'Pembelian Lunas #' . $pembelian->kode_pembelian,
                    now(),
                    false // is_cash = false, tidak ambil dari laci
                );
            }

            $pembelian->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil diselesaikan! Stok dan harga produk telah diperbarui.',
                'data' => $pembelian,
            ]);
        }
        catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'GAGAL: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/pembelian/search-variants
     * Pencarian varian produk (mendukung pencarian manual maupun pencocokan nama nota AI yang di-update)
     */
    public function searchVariants(Request $request)
    {
        try {
            $searchTerm = $request->input('search');

            if (empty($searchTerm)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Parameter pencarian diperlukan'
                ], 400);
            }

            $ownerId = $this->getOwnerId();

            // 1. Pecah search term menjadi kata kunci bersih (tanpa tanda kurung, garis miring, koma, plus)
            $cleanedSearch = preg_replace('/[\/\(\)\-\,\+\.]+/', ' ', strtolower($searchTerm));
            $keywords = array_values(array_unique(array_filter(explode(' ', $cleanedSearch), fn($w) => strlen($w) >= 2)));

            if (empty($keywords)) {
                $keywords = [strtolower(trim($searchTerm))];
            }

            $baseQuery = fn() => ProductVariant::query()
                ->whereHas('sparepart', function ($q) use ($ownerId) {
                    $q->withoutGlobalScope(\App\Scopes\CabangScope::class)
                      ->where('kode_owner', $ownerId);
                })
                ->with([
                    'sparepart' => function ($q) {
                        $q->withoutGlobalScope(\App\Scopes\CabangScope::class)
                          ->with('kategori');
                    },
                    'attributeValues.attribute'
                ]);

            $matchedVariants = collect();

            // Tahap 1: Pencarian ketat (semua keyword cocok) jika jumlah kata kunci pendek (<= 5 kata)
            if (count($keywords) <= 5) {
                $matchedVariants = $baseQuery()
                    ->where(function ($query) use ($keywords) {
                        $query->orWhere(function ($nameQuery) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                $nameQuery->whereHas('sparepart', function ($subQ) use ($keyword) {
                                    $subQ->withoutGlobalScope(\App\Scopes\CabangScope::class)
                                         ->where(function ($innerQ) use ($keyword) {
                                             $innerQ->where(DB::raw("REPLACE(LOWER(nama_sparepart), ',', '.')"), 'LIKE', '%' . $keyword . '%')
                                                    ->orWhere(DB::raw('LOWER(kode_sparepart)'), 'LIKE', '%' . $keyword . '%');
                                         });
                                });
                            }
                        });

                        $query->orWhere(function ($attributeQuery) use ($keywords) {
                            foreach ($keywords as $keyword) {
                                $attributeQuery->whereHas('attributeValues', function ($subQ) use ($keyword) {
                                    $subQ->where(DB::raw('LOWER(value)'), 'LIKE', '%' . $keyword . '%');
                                });
                            }
                        });
                    })
                    ->take(15)
                    ->get();
            }

            // Tahap 2: Jika hasil masih sedikit (< 5) atau nama barang panjang/ada update tipe HP baru dari supplier
            if ($matchedVariants->count() < 5) {
                $stopWords = ['lcd', 'ts', 'fullset', 'black', 'white', 'ori', 'original', 'plus', 'pro', 'max', 'lite', '4g', '5g', 'new', 'dan', 'for'];
                $anchorKeywords = array_values(array_filter($keywords, fn($k) => !in_array($k, $stopWords) && strlen($k) >= 2));
                if (empty($anchorKeywords)) {
                    $anchorKeywords = $keywords;
                }

                // Ambil maksimal 12 kata kunci jangkar terpenting (merek & kode seri HP)
                $searchAnchors = array_slice($anchorKeywords, 0, 12);

                $candidates = $baseQuery()
                    ->whereHas('sparepart', function ($q) use ($searchAnchors) {
                        $q->withoutGlobalScope(\App\Scopes\CabangScope::class)
                          ->where(function ($orQ) use ($searchAnchors) {
                              foreach ($searchAnchors as $anchor) {
                                  $orQ->orWhere(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $anchor . '%');
                              }
                          });
                    })
                    ->take(60)
                    ->get();

                // Urutkan kandidat berdasarkan kemiripan token & kesamaan kode tipe HP
                $scored = $candidates->map(function ($variant) use ($keywords, $anchorKeywords) {
                    if (!$variant->sparepart) {
                        return null;
                    }
                    $spNameLower = strtolower($variant->sparepart->nama_sparepart ?? '');
                    $attrStrLower = strtolower($variant->attributeValues->pluck('value')->implode(' '));
                    $combinedTarget = $spNameLower . ' ' . $attrStrLower;
                    $targetTokens = array_values(array_unique(array_filter(
                        explode(' ', preg_replace('/[\/\(\)\-\,\+\.]+/', ' ', $combinedTarget)),
                        fn($w) => strlen($w) >= 2
                    )));

                    $matchedCount = 0;
                    $anchorMatchedCount = 0;
                    foreach ($keywords as $kw) {
                        if (in_array($kw, $targetTokens) || str_contains($combinedTarget, $kw)) {
                            $matchedCount++;
                        }
                    }
                    foreach ($anchorKeywords as $akw) {
                        if (in_array($akw, $targetTokens) || str_contains($spNameLower, $akw)) {
                            $anchorMatchedCount++;
                        }
                    }

                    $minTokens = max(1, min(count($keywords), count($targetTokens)));
                    $overlapScore = ($matchedCount / $minTokens) + ($anchorMatchedCount * 0.35);

                    return [
                        'variant' => $variant,
                        'score' => $overlapScore,
                        'anchor_matches' => $anchorMatchedCount,
                    ];
                })
                ->filter(fn($item) => $item !== null && $item['anchor_matches'] >= 1)
                ->sortByDesc('score')
                ->pluck('variant');

                $matchedVariants = $matchedVariants
                    ->concat($scored)
                    ->unique('id')
                    ->take(15);
            }

            $variants = $matchedVariants->map(function ($variant) {
                if (!$variant->sparepart) {
                    return null;
                }

                return [
                    'id' => $variant->id,
                    'sparepart' => [
                        'id' => $variant->sparepart->id,
                        'nama_sparepart' => $variant->sparepart->nama_sparepart,
                        'kode_kategori' => $variant->sparepart->kode_kategori,
                        'nama_kategori' => $variant->sparepart->kategori->nama_kategori ?? 'N/A',
                    ],
                    'sku' => $variant->sku,
                    'stock' => $variant->stock,
                    'purchase_price' => $variant->purchase_price,
                    'wholesale_price' => $variant->wholesale_price,
                    'retail_price' => $variant->retail_price,
                    'internal_price' => $variant->internal_price,
                    'attribute_values' => $variant->attributeValues,
                ];
            })->filter();

            return response()->json([
                'success' => true,
                'results' => $variants->values(),
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/suppliers
     * Mendapatkan daftar supplier
     */
    public function getSuppliers()
    {
        try {
            $suppliers = Supplier::where('kode_owner', $this->getOwnerId())
                ->select('id', 'nama_supplier', 'telp_supplier', 'alamat_supplier')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data supplier: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/categories
     * Mendapatkan daftar kategori
     */
    public function getCategories()
    {
        try {
            $categories = KategoriSparepart::where('kode_owner', $this->getOwnerId())
                ->select('id', 'nama_kategori')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * GET /api/categories/{categoryId}/sub-categories
     * Mendapatkan daftar sub-kategori berdasarkan kategori
     */
    public function getSubKategori($categoryId)
    {
        try {
            $subCategories = SubKategoriSparepart::where('kode_owner', $this->getOwnerId())
                ->where('kode_kategori', $categoryId)
                ->select('id', 'nama_sub_kategori')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $subCategories,
            ]);
        }
        catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data sub-kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAttributesByCategory(KategoriSparepart $kategori)    {
        // Pastikan user hanya bisa mengakses kategori miliknya
        if ($kategori->kode_owner != $this->getOwnerId()) {
            return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
        }

        $attributes = $kategori->attributes()->with('values')->get();
        return response()->json($attributes);    }
}
