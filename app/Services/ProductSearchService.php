<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class ProductSearchService
{
    /**
     * Logika utama untuk mencari varian produk.
     *
     * @param string|null $searchTerm Kata kunci pencarian.
     * @param int|null $categoryId ID kategori untuk filter.
     * @param bool $inStockOnly Hanya tampilkan yang ada stok.
     * @param int $limit Jumlah item per halaman.
     * @param int $ownerId ID pemilik/upline.
     * @return LengthAwarePaginator
     */
    public function search(
        ?string $searchTerm,
        ?int $categoryId,
        bool $inStockOnly,
        int $limit,
        int $ownerId
    ): LengthAwarePaginator {
        $query = ProductVariant::query()
            ->with([
                'sparepart:id,nama_sparepart,kode_sparepart,kode_kategori,harga_jual,harga_ecer,harga_pasang,kode_owner,stok_sparepart',
                'sparepart.kategori:id,nama_kategori',
                'attributeValues:id,value,attribute_id',
                'attributeValues.attribute:id,name'
            ])
            ->whereHas('sparepart', function ($q) use ($ownerId) {
                $q->where('kode_owner', $ownerId);
            });

        if ($inStockOnly) {
            $query->where('stock', '>', 0);
        }

        if ($categoryId) {
            $query->whereHas('sparepart', function ($q) use ($categoryId) {
                $q->where('kode_kategori', $categoryId);
            });
        }

        if ($searchTerm) {
            // 1. Pecah search term menjadi beberapa kata kunci (keywords)
            $normalizedInput = str_replace(',', '.', $searchTerm);
            $keywords = array_filter(explode(' ', strtolower($normalizedInput)));

            // =================================================================
            // ✅ LOGIKA PENCARIAN BARU YANG LEBIH AKURAT
            // =================================================================
            $query->where(function ($q) use ($keywords) {
                // Klausa ini mencari varian yang cocok dengan SEMUA keyword
                // di salah satu dari tiga tempat: nama sparepart, nilai atribut, atau SKU.

                // KONDISI 1 (ATAU): Semua keyword ada di NAMA SPAREPART
                $q->orWhere(function ($nameQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '\\b' . preg_quote($keyword, '/') . '\\b';
                        $nameQuery->whereHas('sparepart', function ($subQ) use ($pattern) {
                            $subQ->where(DB::raw("REPLACE(LOWER(nama_sparepart), ',', '.')"), 'REGEXP', $pattern);
                        });
                    }
                });

                // KONDISI 2 (ATAU): Semua keyword ada di NILAI ATRIBUT
                $q->orWhere(function ($attributeQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '\\b' . preg_quote($keyword, '/') . '\\b';
                        $attributeQuery->whereHas('attributeValues', function ($subQ) use ($pattern) {
                            $subQ->where(DB::raw('LOWER(value)'), 'REGEXP', $pattern);
                        });
                    }
                });

                // KONDISI 3 (ATAU): Semua keyword ada di SKU
                $q->orWhere(function ($skuQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $skuQuery->where(DB::raw('LOWER(sku)'), 'LIKE', '%' . $keyword . '%');
                    }
                });
            });
            // =================================================================
        }

        $paginatedVariants = $query->paginate($limit);

        // Langsung format hasilnya di sini
        $paginatedVariants->getCollection()->transform(fn ($variant) => $this->formatVariant($variant));

        return $paginatedVariants;
    }

    /**
     * Helper untuk memformat satu objek varian.
     */
    private function formatVariant(ProductVariant $variant): array
    {
        $attributeString = $variant->attributeValues->map(fn ($av) => $av->value)->join(', ');
        $displayName = $variant->sparepart->nama_sparepart . ($attributeString ? ' - ' . $attributeString : '');

        $stockStatus = 'out_of_stock';
        if ($variant->stock > 5) {
            $stockStatus = 'available';
        } elseif ($variant->stock > 0) {
            $stockStatus = 'low_stock';
        }

        $attributeValueIds = $variant->attributeValues->pluck('id')->toArray();
        $settings = \App\Models\PriceSetting::findBestSetting(
            $variant->sparepart->kode_kategori,
            $attributeValueIds
        );

        $general = $settings['general'];
        $specific = $settings['specific'];

        $finalWarranty = 0;

        if ($general) {
            $finalWarranty = (!empty($specific->warranty_percentage) && $specific->warranty_percentage > 0)
                ? $specific->warranty_percentage
                : $general->warranty_percentage;
        }

        $varStock = (int)($variant->stock > 0 ? $variant->stock : $variant->sparepart->stok_sparepart);
        $retailPrice = (int)($variant->retail_price > 0 ? $variant->retail_price : $variant->sparepart->harga_jual);
        $wholesalePrice = (int)($variant->wholesale_price > 0 ? $variant->wholesale_price : $variant->sparepart->harga_ecer);

        return [
            'id' => $variant->sparepart->id,
            'variant_id' => $variant->id,
            'sparepart_id' => $variant->sparepart->id,
            'nama_sparepart' => $variant->sparepart->nama_sparepart,
            'nama_part' => $variant->sparepart->nama_sparepart,
            'name' => $displayName,
            'display_name' => $displayName,
            'kode_sparepart' => $variant->sku ?: ($variant->sparepart->kode_sparepart ?? ''),
            'code' => $variant->sku ?: ($variant->sparepart->kode_sparepart ?? ''),
            'sku' => $variant->sku,
            'harga_internal' => (int)$variant->internal_price,
            'harga_jual' => $retailPrice,
            'harga_ecer' => $retailPrice,
            'harga_glosir' => $wholesalePrice,
            'harga_grosir' => $wholesalePrice,
            'harga_beli' => (int)$variant->purchase_price,
            'jasa' => (int)$variant->sparepart->harga_pasang,
            'harga_pasang' => (int)$variant->sparepart->harga_pasang,
            'stock' => $varStock,
            'stok_sparepart' => $varStock,
            'total_stock' => $varStock,
            'prices' => [
                'purchase' => $variant->purchase_price,
                'wholesale' => $variant->wholesale_price,
                'retail' => $variant->retail_price,
                'internal' => $variant->internal_price,
            ],
            'warranty_percentage' => (int)$finalWarranty ?? 0,
            'product' => [
                'id' => $variant->sparepart->id,
                'name' => $variant->sparepart->nama_sparepart,
            ],
            'category' => optional($variant->sparepart->kategori)->only(['id', 'nama_kategori']),
            'kategori_sparepart' => optional($variant->sparepart->kategori)->nama_kategori,
            'attributes' => $variant->attributeValues->map(fn ($av) => [
                'name' => $av->attribute->name,
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
            'stock_status' => $stockStatus,
        ];
    }
}
