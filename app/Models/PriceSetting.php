<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PriceSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'kategori_sparepart_id',
        'attribute_value_id',
        'wholesale_margin',
        'retail_margin',
        'internal_margin',
        'default_service_fee',
        'warranty_percentage',
        'kode_owner',
    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriSparepart::class, 'kategori_sparepart_id');
    }

    public function attributeValue()
    {
        return $this->belongsTo(AttributeValue::class);
    }

    /**
     * Cari aturan harga terbaik berdasarkan prioritas.
     * Prioritas 1: Aturan spesifik untuk Nilai Atribut.
     * Prioritas 2: Aturan umum untuk Kategori.
     *
     * @param int $categoryId
     * @param Collection|array $attributeValueIds
     * @return self|null
     */
    public static function findBestSetting(int $categoryId, $attributeValueIds): array
    {
        $attributeValueIds = collect($attributeValueIds);

        // 1. Selalu cari aturan umum sebagai dasar/fallback.
        $generalSetting = self::where('kategori_sparepart_id', $categoryId)
                            ->whereNull('attribute_value_id')
                            ->first();

        // 2. Cari aturan spesifik jika ada nilai atribut yang dipilih.
        $specificSetting = null;
        if ($attributeValueIds->isNotEmpty()) {
            $specificSetting = self::whereIn('attribute_value_id', $attributeValueIds)->first();
        }

        // 3. Kembalikan keduanya dalam sebuah array.
        return [
            'general' => $generalSetting,
            'specific' => $specificSetting,
        ];
    }
}
