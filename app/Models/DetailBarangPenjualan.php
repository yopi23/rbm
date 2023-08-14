<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailBarangPenjualan extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_penjualan',
        'kode_barang',
        'qty_barang',
        'detail_harga_modal',
        'detail_harga_jual',
        'user_input',
    ];
}
