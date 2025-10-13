<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attribute extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'kategori_sparepart_id',
        'kode_owner',

    ];

    public function kategori()
    {
        return $this->belongsTo(KategoriSparepart::class, 'kategori_sparepart_id');
    }
     public function values()
    {
        return $this->hasMany(AttributeValue::class);
    }

}
