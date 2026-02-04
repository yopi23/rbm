<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Sparepart;
use App\Models\HargaKhusus;
use App\Models\ProductVariant;
use App\Models\Attribute;
use App\Models\AttributeValue;
use App\Services\PriceCalculationService; // <-- Pastikan use statement ini ada

class ReviewController extends Controller
{
    /**
     * Menampilkan halaman utama untuk review dan migrasi data sparepart lama.
     */
    public function index(Request $request) // <-- 2. Tambahkan Request $request
    {
        $page = "Review & Migrasi Sparepart Lama";

        // 3. Mulai membangun query, jangan langsung eksekusi
        $query = Sparepart::with(['kategori','subKategori'])
            ->doesntHave('variants');

        // 4. Tambahkan kondisi WHERE jika ada input pencarian
        if ($request->filled('search')) {
            $searchTerm = $request->input('search');
            $query->where('nama_sparepart', 'like', "%{$searchTerm}%");
        }

        // 5. Lanjutkan query dan eksekusi dengan paginate
        $unmigratedSpareparts = $query->orderBy('nama_sparepart')->paginate(50);

        // Penting: Tambahkan ini agar nilai pencarian tetap ada di link paginasi
        $unmigratedSpareparts->appends($request->only('search'));

        $stats = [
            'total_unmigrated' => Sparepart::doesntHave('variants')->count(),
            'total_migrated' => Sparepart::has('variants')->count(),
            'total_with_stock' => Sparepart::doesntHave('variants')->where('stok_sparepart', '>', 0)->count(),
        ];

        $content = view('admin.page.review.index', compact('unmigratedSpareparts', 'stats'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * API untuk mengambil data sparepart dan atribut yang relevan untuk modal.
     */
    public function getMigrationData(Sparepart $sparepart)
    {
        $attributes = Attribute::with('values')
            ->where('kategori_sparepart_id', $sparepart->kode_kategori)
            ->where('kode_owner', $sparepart->kode_owner)
            ->get();

        return response()->json([
            'success' => true,
            'sparepart' => [
                'id' => $sparepart->id,
                'nama' => $sparepart->nama_sparepart,
                'kategori_id' => $sparepart->kode_kategori,
                'kategori_nama' => $sparepart->kategori->nama_kategori ?? 'N/A',
            ],
            'attributes' => $attributes->map(function ($attr) {
                return [
                    'id' => $attr->id,
                    'name' => $attr->name,
                    'values' => $attr->values->map(function ($val) {
                        return ['id' => $val->id, 'value' => $val->value];
                    }),
                ];
            }),
        ]);
    }

    /**
     * Memigrasikan satu sparepart menjadi varian dengan atribut yang dipilih dan harga terstandardisasi.
     */
    public function migrateSingle(Request $request, Sparepart $sparepart, PriceCalculationService $priceCalculator)
    {
        $validated = $request->validate([
            'attributes' => 'nullable|array',
            'attributes.*' => 'exists:attribute_values,id',
        ]);

        DB::beginTransaction();
        try {
            $attributeValueIds = $validated['attributes'] ?? [];

            if ($this->isVariantExists($sparepart->id, $attributeValueIds)) {
                throw new \Exception('Varian dengan kombinasi atribut ini sudah ada.');
            }
            if (empty($sparepart->harga_beli)) {
                throw new \Exception('Harga beli kosong. Harap lengkapi data terlebih dahulu.');
            }

            $variant = $this->createVariant($sparepart, $priceCalculator, $attributeValueIds);
            // $this->archiveOldData($sparepart);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "✅ '{$sparepart->nama_sparepart}' berhasil dimigrasi dengan harga terstandardisasi!",
                'variant_id' => $variant->id,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /**
     * Memigrasikan beberapa sparepart terpilih sebagai varian default dengan harga terstandardisasi.
     */
    public function migrateBulk(Request $request, PriceCalculationService $priceCalculator)
    {
        $validated = $request->validate([
            'sparepart_ids' => 'required|array|max:100',
            'sparepart_ids.*' => 'exists:spareparts,id',
        ]);

        set_time_limit(300);
        DB::beginTransaction();
        try {
            $results = ['success' => [], 'failed' => []];
            $spareparts = Sparepart::whereIn('id', $validated['sparepart_ids'])->whereDoesntHave('variants')->get();

            foreach ($spareparts as $sparepart) {
                try {
                    if (empty($sparepart->harga_beli)) {
                        throw new \Exception('Harga beli kosong');
                    }
                    $variant = $this->createVariant($sparepart, $priceCalculator, []);
                    // $this->archiveOldData($sparepart);
                    $results['success'][] = ['id' => $sparepart->id, 'name' => $sparepart->nama_sparepart];
                } catch (\Exception $e) {
                    $results['failed'][] = ['id' => $sparepart->id, 'name' => $sparepart->nama_sparepart, 'error' => $e->getMessage()];
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => sprintf('Proses selesai. ✅ Berhasil: %d | ❌ Gagal: %d', count($results['success']), count($results['failed'])),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => '❌ Migrasi bulk gagal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Memigrasikan beberapa sparepart terpilih dengan atribut spesifik dan harga terstandardisasi.
     */
    public function migrateBulkWithAttributes(Request $request, PriceCalculationService $priceCalculator)
    {
        $validated = $request->validate([
            'items' => 'required|array|max:100',
            'items.*.id' => 'required|exists:spareparts,id',
            'items.*.attributes' => 'nullable|array',
            'items.*.attributes.*' => 'exists:attribute_values,id',
        ]);

        set_time_limit(300);
        DB::beginTransaction();
        try {
            $results = ['success' => [], 'failed' => []];
            foreach ($validated['items'] as $itemData) {
                $sparepart = Sparepart::find($itemData['id']);
                $attributeValueIds = $itemData['attributes'] ?? [];
                if ($sparepart->variants()->exists()) continue;

                try {
                    if (empty($sparepart->harga_beli)) {
                        throw new \Exception('Harga beli kosong');
                    }
                    if ($this->isVariantExists($sparepart->id, $attributeValueIds)) {
                        throw new \Exception('Varian dengan atribut ini sudah ada');
                    }
                    $variant = $this->createVariant($sparepart, $priceCalculator, $attributeValueIds);
                    // $this->archiveOldData($sparepart);
                    $results['success'][] = ['id' => $sparepart->id, 'name' => $sparepart->nama_sparepart];
                } catch (\Exception $e) {
                    $results['failed'][] = ['id' => $sparepart->id, 'name' => $sparepart->nama_sparepart, 'error' => $e->getMessage()];
                }
            }
            DB::commit();
            return response()->json([
                'success' => true,
                'message' => sprintf('Proses selesai. ✅ Berhasil: %d | ❌ Gagal: %d', count($results['success']), count($results['failed'])),
                'data' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => '❌ Terjadi error fatal: ' . $e->getMessage()], 500);
        }
    }

    /**
     * API untuk menampilkan preview data sebelum migrasi, termasuk harga yang akan dihitung ulang.
     */
    public function previewMigration(Sparepart $sparepart, PriceCalculationService $priceCalculator)
    {
        $calculatedPrices = $priceCalculator->calculate(
            $sparepart->harga_beli,
            $sparepart->kode_kategori,
            []
        );

        $willBecomePrices = [
            'stock' => $sparepart->stok_sparepart,
            'purchase_price' => $sparepart->harga_beli,
            'wholesale_price' => $calculatedPrices['wholesale_price'] ,
            'retail_price' => $calculatedPrices['retail_price'] ?? $sparepart->harga_ecer,
            'internal_price' => $calculatedPrices['internal_price'] ?? $sparepart->harga_jual,
        ];

        return response()->json([
            'success' => true,
            'sparepart' => [
                'id' => $sparepart->id,
                'nama' => $sparepart->nama_sparepart,
                'kategori' => $sparepart->kategori->nama_kategori ?? 'N/A',
            ],
            'current_data' => [
                'stok' => $sparepart->stok_sparepart,
                'harga_beli' => $sparepart->harga_beli,
                'harga_jual' => $sparepart->harga_jual,
            ],
            'will_become' => $willBecomePrices,
        ]);
    }

    /**
     * Memulai proses migrasi semua data di background.
     */
    public function migrateAll(Request $request)
    {
        $total = Sparepart::doesntHave('variants')->count();
        if ($total === 0) {
            return response()->json(['success' => true, 'message' => '✅ Semua data sudah dimigrasi!']);
        }
        // Pastikan Anda sudah membuat Job ini: php artisan make:job MigrateAllSparepartsJob
        \App\Jobs\MigrateAllSparepartsJob::dispatch($request->user()->id);
        return response()->json(['success' => true, 'message' => "🚀 Proses migrasi {$total} item dimulai di background."]);
    }

    /**
     * API untuk mengecek status progress migrasi.
     */
    public function checkMigrationStatus()
    {
        $unmigrated = Sparepart::doesntHave('variants')->count();
        $migrated = Sparepart::has('variants')->count();
        $total = $unmigrated + $migrated;
        $percentage = $total > 0 ? round(($migrated / $total) * 100, 2) : 0;

        return response()->json([
            'migrated' => $migrated,
            'unmigrated' => $unmigrated,
            'percentage' => $percentage,
        ]);
    }

    /**
     * Membatalkan migrasi untuk satu sparepart (mengembalikan data dari varian ke data lama).
     */
    public function rollbackMigration(Sparepart $sparepart)
    {
        DB::beginTransaction();
        try {
            $variant = $sparepart->variants()->first();
            if (!$variant) {
                throw new \Exception('Tidak ada varian untuk di-rollback.');
            }

            $sparepart->update([
                'harga_beli' => $variant->purchase_price,
                'harga_jual' => $variant->wholesale_price,
                'harga_ecer' => $variant->retail_price,
                'harga_pasang' => $calculatedPrices['default_service_fee'],
                'stok_sparepart' => $variant->stock,
            ]);

            $sparepart->variants()->delete();
            DB::commit();

            return response()->json(['success' => true, 'message' => "✅ Rollback berhasil untuk '{$sparepart->nama_sparepart}'"]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => "❌ Rollback gagal: {$e->getMessage()}"], 422);
        }
    }

    // ============================================
    // METODE BANTU (HELPERS)
    // ============================================

    /**
     * Metode inti untuk membuat varian baru dengan harga terstandardisasi.
     */
    private function createVariant(Sparepart $sparepart, PriceCalculationService $priceCalculator, array $attributeValueIds = [])
    {
        $calculatedPrices = $priceCalculator->calculate(
            $sparepart->harga_beli,
            $sparepart->kode_kategori,
            $attributeValueIds
        );

        if (is_null($calculatedPrices)) {
            throw new \Exception('Tidak ada aturan harga yang valid ditemukan untuk kategori sparepart ini. Silakan atur harga terlebih dahulu.');
        }

        $variant = ProductVariant::create([
            'sparepart_id' => $sparepart->id,
            'purchase_price' => (int) $sparepart->harga_beli,
            'stock' => (int) $sparepart->stok_sparepart,
            'wholesale_price' => $calculatedPrices['wholesale_price'],
            'retail_price' => $calculatedPrices['retail_price'],
            'internal_price' => $calculatedPrices['internal_price'],
        ]);
        $sparepart->update([
                'harga_beli' => $variant->purchase_price,
                'harga_jual' => $variant->retail_price,
                'harga_ecer' => $variant->retail_price,
                'harga_pasang' => $calculatedPrices['default_service_fee'],
                'stok_sparepart' => $variant->stock,
            ]);

        $this->updateHargaKhusus($sparepart, $calculatedPrices);

        if (!empty($attributeValueIds)) {
            $variant->attributeValues()->attach($attributeValueIds);
        }
        return $variant;
    }

    /**
     * Memeriksa apakah varian dengan kombinasi atribut yang sama sudah ada.
     */
    private function isVariantExists($sparepartId, $attributeValueIds)
    {
        if (empty($attributeValueIds)) {
            return ProductVariant::where('sparepart_id', $sparepartId)->doesntHave('attributeValues')->exists();
        }

        $query = ProductVariant::where('sparepart_id', $sparepartId)
            ->whereHas('attributeValues', function ($q) use ($attributeValueIds) {
                $q->whereIn('attribute_value_id', $attributeValueIds);
            }, '=', count($attributeValueIds));

        return $query->exists();
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
     * Mengarsipkan data lama (mengosongkan stok dan harga) setelah migrasi berhasil.
     */
    private function archiveOldData(Sparepart $sparepart)
    {
        $sparepart->update([
            'stok_sparepart' => 0,
            'harga_beli' => 0,
            'harga_jual' => 0,
            'harga_ecer' => 0,
            'harga_pasang' => 0,
        ]);
    }

}
