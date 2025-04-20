<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customer_table extends Model
{
    use HasFactory;
    protected $fillable=[
        'nama_pelanggan',
        'nama_toko',
        'alamat_toko',
        'status_toko',
        'nomor_toko',
        'kode_toko',
        'kode_owner',

    ];
}
