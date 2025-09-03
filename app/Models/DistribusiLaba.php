<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistribusiLaba extends Model
{
    use HasFactory;
    protected $table = 'distribusi_laba';
    protected $fillable = [
        'laba_kotor', 'laba_bersih', 'alokasi_owner', 'alokasi_investor',
        'alokasi_karyawan', 'alokasi_kas_aset', 'kode_owner', 'tanggal',
        'tanggal_mulai', 'tanggal_selesai',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];
    /**
     * Satu event distribusi laba menghasilkan BANYAK entri kas (satu untuk tiap alokasi).
     */
    public function kasEntries()
    {
        return $this->morphMany(KasPerusahaan::class, 'sourceable');
    }
}
