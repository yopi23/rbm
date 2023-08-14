<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PemasukkanLain extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_pemasukkan',
        'judul_pemasukan',
        'catatan_pemasukkan',
        'jumlah_pemasukkan',
        'kode_owner',
    ];
}
