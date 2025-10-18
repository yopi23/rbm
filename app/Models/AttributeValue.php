<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttributeValue extends Model
{
    use HasFactory;

    protected $fillable = [
        'attribute_id',
        'value',
        'kode_owner',

    ];

    // public function priceSetting()
    // {
    //     return $this->hasOne(PriceSetting::class);
    // }
    public function attribute()
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Relasi ke varian produk.
     */
    public function productVariants()
    {
        return $this->belongsToMany(ProductVariant::class);
    }

    /**
     * Aturan harga spesifik yang dimiliki oleh nilai atribut ini.
     */
    public function priceSetting()
    {
        return $this->hasOne(PriceSetting::class);
    }

    public function variants()
    {
        return $this->belongsToMany(
            ProductVariant::class,
            'attribute_value_product_variant',
            'attribute_value_id',
            'product_variant_id'
        );
    }
}
