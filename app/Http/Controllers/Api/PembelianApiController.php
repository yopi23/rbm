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
use App\Models\HargaKhusus;
use App\Models\SubKategoriSparepart;
use App\Services\PriceCalculationService;
use App\Traits\ManajemenKasTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\Hutang;

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
    public function index()
    {
        try {
            $pembelians = Pembelian::where('kode_owner', $this->getOwnerId())
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
                        'created_at' => $pembelian->created_at->toIso8601String(),
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $pembelians,
            ]);
        } catch (\Exception $e) {
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
            } else {
                $kode_pembelian .= '001';
            }

            $pembelian = Pembelian::create([
                'kode_pembelian' => $kode_pembelian,
                'tanggal_pembelian' => date('Y-m-d'),
                'total_harga' => 0,
                'kode_owner' => $this->getOwnerId(),
                'status' => 'draft',
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
        } catch (\Exception $e) {
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

            $details = DetailPembelian::with([
                'productVariant.sparepart',
                'productVariant.attributeValues.attribute'
            ])
                ->where('pembelian_id', $id)
                ->get()
                ->map(function ($detail) {
                    $variants = [];
                    if ($detail->productVariant && $detail->productVariant->attributeValues) {
                        foreach ($detail->productVariant->attributeValues as $attrValue) {
                            $variants[] = [
                                'attribute_name' => $attrValue->attribute->name ?? '',
                                'value' => $attrValue->value,
                            ];
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
                        'item_kategori' => $detail->item_kategori,
                        'attributes' => $detail->attributes,
                        'variants' => $variants,
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
                $detailData['item_kategori'] = $validated['item_kategori'];
                $detailData['attributes'] = json_encode($validated['attributes'] ?? []);
            } else {
                $detailData['product_variant_id'] = $validated['product_variant_id'];
                $detailData['item_kategori'] = $validated['item_kategori'];
                $detailData['attributes'] = json_encode($validated['attributes'] ?? []);

                $variant = ProductVariant::find($validated['product_variant_id']);
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
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambah item: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * PUT /api/pembelian/items/{detailId}
     * Update item pembelian
     */
    public function updateItem(Request $request, $detailId)
    {
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'harga_beli' => 'required|numeric|min:0',
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

            $updateData = [
                'nama_item' => $validated['nama_item'],
                'jumlah' => $validated['jumlah'],
                'harga_beli' => $validated['harga_beli'],
                'total' => $validated['jumlah'] * $validated['harga_beli'],
                'item_kategori' => $validated['item_kategori'],
                'attributes' => json_encode($validated['attributes'] ?? []),
            ];

            if (!$detail->is_new_item) {
                $updateData['product_variant_id'] = $validated['product_variant_id'];
                $variant = ProductVariant::find($validated['product_variant_id']);
                if ($variant) {
                    $updateData['sparepart_id'] = $variant->sparepart_id;
                }
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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

                    $sparepart = Sparepart::firstOrCreate(
                        [
                            'nama_sparepart' => $detail->nama_item,
                            'kode_kategori' => $detail->item_kategori
                        ],
                        [
                            'kode_sparepart' => 'SP-' . date('YmdHis') . rand(100, 999),
                            'kode_owner' => $this->getOwnerId(),
                            'kode_spl' => $validated['supplier'],
                            'foto_sparepart' => '-',
                            'desc_sparepart' => null,
                            'stok_sparepart' => $detail->jumlah,
                            'harga_beli' => $detail->harga_beli,
                            'harga_jual' => $calculatedPrices['internal_price'],
                            'harga_ecer' => $calculatedPrices['wholesale_price'],
                            'harga_pasang' => $calculatedPrices['default_service_fee'] ?? 0,
                            'kode_sub_kategori' => null,
                            'stock_asli' => null,
                        ]
                    );

                    $variant = $sparepart->variants()->create([
                        'purchase_price' => $detail->harga_beli,
                        'stock' => $detail->jumlah,
                        'wholesale_price' => $calculatedPrices['wholesale_price'],
                        'retail_price' => $calculatedPrices['retail_price'],
                        'internal_price' => $calculatedPrices['internal_price'],
                    ]);

                    if (!empty($attributeValueIds)) {
                        $variant->attributeValues()->sync(array_values($attributeValueIds));
                    }

                    $detail->product_variant_id = $variant->id;
                    $detail->save();
                    $sparepart->save();
                    $this->updateHargaKhusus($sparepart, $calculatedPrices);

                } else {
                    // Proses restock
                    $variant = ProductVariant::find($detail->product_variant_id);

                    if ($variant) {
                        $sparepart = Sparepart::find($detail->sparepart_id);

                        if (!$sparepart) {
                            throw new \Exception('Data sparepart tidak ditemukan untuk item restock: "' . $detail->nama_item . '"');
                        }

                        $categoryId = $sparepart->kode_kategori;
                        $attributeValueIds = array_filter(json_decode($detail->attributes, true) ?: []);

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

                        $variant->stock = $new_total_stock;
                        $variant->purchase_price = $new_average_cost;
                        $variant->wholesale_price = $calculatedPrices['wholesale_price'];
                        $variant->retail_price = $calculatedPrices['retail_price'];
                        $variant->internal_price = $calculatedPrices['internal_price'];
                        $variant->save();

                        $sparepart->stok_sparepart = $new_total_stock;
                        $sparepart->harga_beli = $new_stock_cost;
                        $sparepart->harga_pasang = $calculatedPrices['default_service_fee'] ?? 0;
                        $sparepart->save();
$this->updateHargaKhusus($sparepart, $calculatedPrices);
                    }
                }
            }

            // Update pembelian
            $pembelian->supplier = $supplier->nama_supplier;
            $pembelian->status = 'selesai';
            $pembelian->metode_pembayaran = $validated['metode_pembayaran'];

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
            } else {
                $pembelian->status_pembayaran = 'Lunas';

                $this->catatKas(
                    $pembelian,
                    0,
                    $pembelian->total_harga,
                    'Pembelian Lunas #' . $pembelian->kode_pembelian,
                    now()
                );
            }

            $pembelian->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pembelian berhasil diselesaikan! Stok dan harga produk telah diperbarui.',
                'data' => $pembelian,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'GAGAL: ' . $e->getMessage(),
            ], 500);
        }
    }

    protected function updateHargaKhusus($sparepart, $calculatedPrices)
{
    HargaKhusus::updateOrCreate(
        ['id_sp' => $sparepart->id],
        [
            'harga_toko'   =>  0,
            'harga_satuan' => $calculatedPrices['retail_price'] ?? 0,
        ]
    );
}

    /**
     * GET /api/pembelian/search-variants
     * Pencarian varian produk
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

        // 1. Pecah search term menjadi beberapa kata kunci (keywords)
        $normalizedInput = str_replace(',', '.', $searchTerm);
        $keywords = array_filter(explode(' ', strtolower($normalizedInput)));

        $variants = ProductVariant::query()
            ->whereHas('sparepart', function ($q) use ($ownerId) {
                $q->where('kode_owner', $ownerId);
            })
            // Eager loading Anda sudah benar
            ->with(['sparepart.kategori', 'attributeValues.attribute'])

            // =================================================================
            // ✅ PERBAIKAN LOGIKA PENCARIAN AKURAT DI SINI
            // =================================================================
            ->where(function ($query) use ($keywords) {

                // KONDISI 1 (ATAU): Semua keyword ada di NAMA SPAREPART
                $query->orWhere(function ($nameQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]';
                        $nameQuery->whereHas('sparepart', function ($subQ) use ($pattern) {
                            $subQ->where(DB::raw("REPLACE(LOWER(nama_sparepart), ',', '.')"), 'REGEXP', $pattern);
                        });
                    }
                });

                // KONDISI 2 (ATAU): Semua keyword ada di NILAI ATRIBUT
                $query->orWhere(function ($attributeQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '[[:<:]]' . preg_quote($keyword, '/') . '[[:>:]]';
                        $attributeQuery->whereHas('attributeValues', function ($subQ) use ($pattern) {
                            $subQ->where(DB::raw('LOWER(value)'), 'REGEXP', $pattern);
                        });
                    }
                });

            })
            // =================================================================
            // AKHIR PERBAIKAN
            // =================================================================

            ->take(10)
            ->get()
            ->map(function ($variant) {
                // Sisa dari logika 'map' Anda sudah benar dan tidak diubah
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
    } catch (\Exception $e) {
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
        } catch (\Exception $e) {
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
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data kategori: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function getAttributesByCategory(KategoriSparepart $kategori)
{
    // Pastikan user hanya bisa mengakses kategori miliknya
    if ($kategori->kode_owner != $this->getOwnerId()) {
        return response()->json(['success' => false, 'message' => 'Akses ditolak.'], 403);
    }

    $attributes = $kategori->attributes()->with('values')->get();
    return response()->json($attributes);
}
}
