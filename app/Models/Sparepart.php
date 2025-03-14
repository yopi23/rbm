<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_sparepart',
        'kode_kategori',
        'foto_sparepart',
        'nama_sparepart',
        'desc_sparepart',
        'stok_sparepart',
        'stock_asli',
        'harga_beli',
        'harga_jual',
        'harga_ecer',
        'harga_pasang',
        'kode_owner',
        'kode_spl',
    ];
    // public function detailSparepart()
    // {
    //     return $this->hasMany(DetailSparepartPenjualan::class, 'kode_sparepart', 'id');
    // }
}
