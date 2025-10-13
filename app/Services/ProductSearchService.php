<?php

namespace App\Services;

use App\Models\ProductVariant;
use Illuminate\Pagination\LengthAwarePaginator;

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
    ): LengthAwarePaginator
    {
        $query = ProductVariant::query()
            ->with([
                'sparepart:id,nama_sparepart,kode_kategori',
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
            $query->where(function ($q) use ($searchTerm) {
                $q->whereHas('sparepart', function ($subQ) use ($searchTerm) {
                    $subQ->where('nama_sparepart', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orWhereHas('attributeValues', function ($subQ) use ($searchTerm) {
                    $subQ->where('value', 'LIKE', '%' . $searchTerm . '%');
                })
                ->orWhere('sku', 'LIKE', '%' . $searchTerm . '%');
            });
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

        return [
            'variant_id' => $variant->id,
            'display_name' => $displayName,
            'sku' => $variant->sku,
            'stock' => $variant->stock,
            'prices' => [
                'purchase' => $variant->purchase_price,
                'wholesale' => $variant->wholesale_price,
                'retail' => $variant->retail_price,
                'internal' => $variant->internal_price,
            ],
            'product' => [
                'id' => $variant->sparepart->id,
                'name' => $variant->sparepart->nama_sparepart,
            ],
            'category' => optional($variant->sparepart->kategori)->only(['id', 'name']),
            'attributes' => $variant->attributeValues->map(fn ($av) => [
                'name' => $av->attribute->name,
                'value' => $av->value,
            ]),
            'stock_status' => $stockStatus,
        ];
    }
}
