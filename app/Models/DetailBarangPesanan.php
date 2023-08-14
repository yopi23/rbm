<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailBarangPesanan extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_pesanan',
        'kode_barang',
        'detail_modal_pesan',
        'detail_harga_pesan',
        'qty_barang',
    ];
}
