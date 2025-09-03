<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AlokasiLaba extends Model
{
    use HasFactory;

    protected $table = 'alokasi_laba';

    protected $fillable = [
        'distribusi_laba_id',
        'kode_owner',
        'user_id',
        'role',
        'jumlah',
        'status',
        'penarikan_id',
    ];

    /**
     * Relasi untuk mengambil data log distribusi utama.
     */
    public function distribusiLaba()
    {
        return $this->belongsTo(DistribusiLaba::class, 'distribusi_laba_id');
    }
    public function kas()
{
    return $this->morphOne(KasPerusahaan::class, 'sourceable');
}
}
