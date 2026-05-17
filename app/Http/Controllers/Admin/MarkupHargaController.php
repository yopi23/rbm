<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\KategoriSparepart;
use App\Models\Sparepart;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class MarkupHargaController extends Controller
{
    /**
     * Menampilkan halaman antarmuka Manajemen Harga Cepat (Markup).
     */
    public function index()
    {
        $page = "Markup & Penyesuaian Harga Cepat";
        
        // Ambil semua kategori yang dimiliki owner ini
        $categories = KategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)
                        ->orderBy('nama_kategori')
                        ->get();

        $content = view('admin.page.pengaturan_harga.markup', compact('categories', 'page'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * AJAX endpoint untuk mengambil data sparepart & varian berdasarkan filter.
     */
    public function getData(Request $request)
    {
        try {
            $query = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
                              ->with(['variants.attributeValues', 'kategori']);

            if ($request->filled('kategori_id')) {
                $query->where('kode_kategori', $request->kategori_id);
            }

            // Jika ada filter tambahan seperti search nama
            if ($request->filled('search')) {
                $query->where('nama_sparepart', 'like', '%' . $request->search . '%');
            }

            // Ambil maksimal 500 data untuk performa
            $spareparts = $query->take(500)->get();

            // Transformasi data agar mudah dibaca di frontend (tabel)
            $data = [];
            foreach ($spareparts as $sp) {
                // Jika memiliki varian
                if ($sp->variants && $sp->variants->count() > 0) {
                    foreach ($sp->variants as $variant) {
                        $variantName = $sp->nama_sparepart;
                        
                        // Menambahkan nama atribut ke nama varian jika ada (misal: "LCD Vivo Y12 - Original")
                        $attributes = [];
                        if ($variant->attributeValues) {
                            foreach ($variant->attributeValues as $attr) {
                                $attributes[] = $attr->value;
                            }
                        }
                        if (!empty($attributes)) {
                            $variantName .= ' - ' . implode(', ', $attributes);
                        }

                        $data[] = [
                            'id' => 'v_' . $variant->id,
                            'kategori' => $sp->kategori ? $sp->kategori->nama_kategori : '-',
                            'sku' => $variant->sku ?? $sp->kode_sparepart,
                            'nama_produk' => $variantName,
                            'harga_beli' => $variant->purchase_price ?? $sp->harga_beli ?? 0,
                            'harga_jual' => $variant->retail_price ?? $sp->harga_jual ?? 0,
                            'harga_ecer' => $variant->wholesale_price ?? $sp->harga_ecer ?? 0,
                            'harga_internal' => $variant->internal_price ?? 0,
                            'tipe' => 'variant',
                            'db_id' => $variant->id,
                            'parent_id' => $sp->id
                        ];
                    }
                } else {
                    // Jika tidak ada varian, ambil dari sparepart langsung
                    $data[] = [
                        'id' => 's_' . $sp->id,
                        'kategori' => $sp->kategori ? $sp->kategori->nama_kategori : '-',
                        'sku' => $sp->kode_sparepart,
                        'nama_produk' => $sp->nama_sparepart,
                        'harga_beli' => $sp->harga_beli ?? 0,
                        'harga_jual' => $sp->harga_jual ?? 0,
                        'harga_ecer' => $sp->harga_ecer ?? 0,
                        'harga_internal' => 0, // Fallback
                        'tipe' => 'sparepart',
                        'db_id' => $sp->id,
                        'parent_id' => $sp->id
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Endpoint untuk menyimpan perubahan harga secara massal.
     */
    public function update(Request $request)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*.tipe' => 'required|in:variant,sparepart',
            'items.*.db_id' => 'required|integer',
            'items.*.parent_id' => 'required|integer',
            'items.*.harga_jual' => 'required|numeric',
            'items.*.harga_ecer' => 'required|numeric',
            'items.*.harga_internal' => 'required|numeric',
        ]);

        DB::beginTransaction();

        try {
            $ownerId = $this->getThisUser()->id_upline;
            $items = $request->items;
            
            // Kita kumpulkan parent_id yang perlu diupdate sync-nya
            $updatedSparepartIds = [];

            foreach ($items as $item) {
                if ($item['tipe'] === 'variant') {
                    ProductVariant::where('id', $item['db_id'])
                        ->whereHas('sparepart', function($q) use ($ownerId) {
                            $q->where('kode_owner', $ownerId);
                        })
                        ->update([
                            'retail_price' => $item['harga_jual'],
                            'wholesale_price' => $item['harga_ecer'],
                            'internal_price' => $item['harga_internal'],
                        ]);
                        
                    $updatedSparepartIds[] = $item['parent_id'];
                } else if ($item['tipe'] === 'sparepart') {
                    Sparepart::where('id', $item['db_id'])
                        ->where('kode_owner', $ownerId)
                        ->update([
                            'harga_jual' => $item['harga_internal'] > 0 ? $item['harga_internal'] : $item['harga_jual'],
                            'harga_ecer' => $item['harga_ecer'],
                        ]);
                }
            }

            // Sync harga ke parent Sparepart (jika varian pertama diupdate)
            $uniqueSparepartIds = array_unique($updatedSparepartIds);
            foreach ($uniqueSparepartIds as $spId) {
                $firstVariant = ProductVariant::where('sparepart_id', $spId)->orderBy('id')->first();
                if ($firstVariant) {
                    Sparepart::where('id', $spId)
                        ->where('kode_owner', $ownerId)
                        ->update([
                            'harga_jual' => $firstVariant->internal_price ?? $firstVariant->retail_price,
                            'harga_ecer' => $firstVariant->wholesale_price,
                        ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => count($items) . ' produk berhasil diperbarui harganya.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan harga: ' . $e->getMessage()
            ], 500);
        }
    }
}
