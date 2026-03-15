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
        'jumlah_cash',
        'jumlah_transfer',
        'metode_bayar',
        'kode_owner',
        'shift_id'
    ];

    /**
     * Relasi polymorphic ke KasPerusahaan
     */
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class , 'sourceable');
    }

    /**
     * Relasi ke Shift
     */
    public function shift()
    {
        return $this->belongsTo(Shift::class);
    }

    /**
     * Relasi ke Owner (User)
     */
    public function owner()
    {
        return $this->belongsTo(User::class , 'kode_owner', 'id');
    }
}
