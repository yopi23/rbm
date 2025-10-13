<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

     protected $fillable = [
        'sparepart_id',
        'sku',
        'purchase_price',
        'wholesale_price',
        'retail_price',
        'internal_price',
        'stock',
        'kode_owner',
    ];


    /**
     * Relasi "belongsTo" ke model Sparepart (Produk Induk).
     * Setiap varian pasti milik satu produk dasar.
     */
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }

    /**
     * Relasi "belongsToMany" ke model AttributeValue.
     * Satu varian bisa memiliki beberapa nilai atribut (misal: Kualitas & Merek).
     * Relasi ini menggunakan tabel pivot 'attribute_value_product_variant'.
     */
    public function attributeValues()
    {
        return $this->belongsToMany(AttributeValue::class, 'attribute_value_product_variant')
                    ->with('attribute');
    }
}
