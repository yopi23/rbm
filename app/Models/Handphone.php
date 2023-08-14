<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Handphone extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_barang',
        'kode_kategori',
        'foto_barang',
        'nama_barang',
        'desc_barang',
        'stok_barang',
        'merk_barang',
        'kondisi_barang',
        'harga_beli_barang',
        'harga_jual_barang',
        'status_barang',
        'kode_owner',
    ];
}
