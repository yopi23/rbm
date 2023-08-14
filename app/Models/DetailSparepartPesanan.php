<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailSparepartPesanan extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_pesanan',
        'kode_sparepart',
        'detail_modal_pesan',
        'detail_harga_pesan',
        'qty_sparepart',
    ];
}
