<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranToko extends Model
{
    use HasFactory;
    protected $fillable = [
        'tanggal_pengeluaran',
        'nama_pengeluaran',
        'catatan_pengeluaran',
        'jumlah_pengeluaran',
        'kode_owner',
    ];
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}
