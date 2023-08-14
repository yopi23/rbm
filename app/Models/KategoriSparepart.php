<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'foto_kategori',
        'nama_kategori',
        'kode_owner',
    ];
}
