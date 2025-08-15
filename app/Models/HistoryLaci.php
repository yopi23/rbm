<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoryLaci extends Model
{
    use HasFactory;
    // Nama tabel yang sesuai dengan model
    protected $table = 'history_laci';

    // Kolom yang dapat diisi secara massal
    protected $fillable = [
        'kode_owner',
        'id_kategori',
        'masuk',
        'keluar',
        'keterangan',
        'reference_type',
        'reference_id',
        'reference_code'
    ];

    // Jika Anda menggunakan timestamps
    public $timestamps = true;

    // Anda bisa menambahkan relasi jika diperlukan
    public function laci()
    {
        return $this->belongsTo(Laci::class, 'id_laci');
    }
}
