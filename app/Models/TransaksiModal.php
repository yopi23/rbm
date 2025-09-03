<?php

namespace App\Models; // <-- PASTIKAN NAMESPACE INI BENAR

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// v-- PASTIKAN NAMA CLASS INI BENAR
class TransaksiModal extends Model
{
    use HasFactory;

    // Tentukan nama tabel secara eksplisit
    protected $table = 'transaksi_modal';

    // Kolom yang bisa diisi
    protected $fillable = [
        'tanggal',
        'kode_owner',
        'jenis_transaksi',
        'jumlah',
        'keterangan',
    ];

    /**
     * Setiap transaksi modal memiliki satu entri di kas perusahaan.
     * Ini adalah relasi polimorfik.
     */
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}
