<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class customer_table extends Model
{
    use HasFactory;
    protected $fillable=[
        'nama_kontak',
        'nama_toko',
        'alamat',
        'tipe_pelanggan',
        'nomor_telepon',
        'kode_toko',
        'kode_owner',

    ];
}
