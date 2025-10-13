<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'foto_kategori',
        'nama_kategori',
        'kode_owner',
    ];

    public function spareparts()
    {
        return $this->hasMany(Sparepart::class, 'kode_kategori', 'kode_kategori');
    }
    public function subKategori()
    {
        return $this->hasMany(SubKategoriSparepart::class, 'kategori_id');
    }

    public function priceSetting()
    {
        return $this->hasOne(PriceSetting::class);
    }

    public function attributes()
    {
        return $this->hasMany(Attribute::class);
    }
}
