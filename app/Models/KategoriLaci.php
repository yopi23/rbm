<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriLaci extends Model
{
    use HasFactory;

    // Nama tabel yang sesuai dengan model
    protected $table = 'kategori_lacis';

    // Kolom yang dapat diisi secara massal
    protected $fillable = ['kode_owner', 'name_laci'];

    // Jika Anda menggunakan timestamps
    public $timestamps = true;

    // Anda bisa menambahkan relasi jika diperlukan
}
