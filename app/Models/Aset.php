<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Aset extends Model
{
    use HasFactory;

    protected $table = 'asets';

    protected $fillable = [
        'kode_owner',
        'nama_aset',
        'kategori_aset',
        'tanggal_perolehan',
        'nilai_perolehan',
        'keterangan',
        'masa_manfaat_bulan', // Tambahkan ini
        'nilai_residu',       // Tambahkan ini
        'penyusutan_terakumulasi', // Tambahkan ini
        'nilai_buku',         // Tambahkan ini
        'shift_id',
    ];

    /**
     * Setiap pembelian aset bisa memiliki satu entri di kas perusahaan.
     */
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}
