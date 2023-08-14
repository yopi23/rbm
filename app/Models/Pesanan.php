<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pesanan extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_pesanan',
        'kode_pesanan',
        'kode_owner',
        'nama_pemesan',
        'alamat',
        'no_telp',
        'email',
        'status_pesanan',
        'catatan_pesanan',
    ];
}
