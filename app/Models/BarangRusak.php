<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BarangRusak extends Model
{
    use HasFactory;
    protected $fillable = [
       'tgl_rusak_barang',
       'kode_barang',
       'jumlah_rusak',
       'catatan_rusak',
       'user_input',
       'kode_owner',
    ];
}
