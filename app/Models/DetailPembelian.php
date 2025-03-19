<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPembelian extends Model
{
    use HasFactory;
    protected $fillable = [
        'pembelian_id',
        'sparepart_id',
        'nama_item',
        'jumlah',
        'harga_beli',
        'harga_jual',    // Kolom baru
        'harga_ecer',    // Kolom baru
        'harga_pasang',
        'total',
        'is_new_item',
    ];

    public function pembelian()
    {
        return $this->belongsTo(Pembelian::class);
    }

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }
}
