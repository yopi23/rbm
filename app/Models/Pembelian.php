<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pembelian extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_pembelian',
        'tanggal_pembelian',
        'supplier',
        'keterangan',
        'total_harga',
        'status',
        'kode_owner',
        'metode_pembayaran',
        'status_pembayaran',
        'tgl_jatuh_tempo'
    ];

    public function detailPembelians()
    {
        return $this->hasMany(DetailPembelian::class);
    }
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }

}
