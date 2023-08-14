<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranOperasional extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_pengeluaran',
        'nama_pengeluaran',
        'kategori',
        'kode_pegawai',
        'jml_pengeluaran',
        'desc_pengeluaran',
        'kode_owner',
    ];
}
