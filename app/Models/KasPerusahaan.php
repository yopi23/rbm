<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KasPerusahaan extends Model
{
    use HasFactory;

    // Tentukan nama tabel secara eksplisit
    protected $table = 'kas_perusahaan';

    // Kolom yang bisa diisi
    protected $fillable = [
        'sourceable_id',
        'sourceable_type',
        'kode_owner',
        'tanggal',
        'deskripsi',
        'debit',
        'kredit',
        'saldo',
    ];

    /**
     * Relasi polimorfik untuk mendapatkan sumber transaksi.
     * (Bisa dari TransaksiModal, Penjualan, PengeluaranOperasional, dll)
     */
    public function sourceable()
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke user (pemilik)
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'kode_owner', 'id');
    }
}
