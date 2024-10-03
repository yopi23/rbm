<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HistoriLaci extends Model
{
    use HasFactory;
    // Nama tabel yang sesuai dengan model
    protected $table = 'history_laci';

    // Kolom yang dapat diisi secara massal
    protected $fillable = ['kode_owner', 'id_laci', 'keterangan'];

    // Jika Anda menggunakan timestamps
    public $timestamps = true;

    // Anda bisa menambahkan relasi jika diperlukan
    public function laci()
    {
        return $this->belongsTo(Laci::class, 'id_laci');
    }
}
