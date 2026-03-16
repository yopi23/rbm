<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranToko extends Model
{
    use HasFactory;

    protected function serializeDate(\DateTimeInterface $date)
    {
        return $date->format('Y-m-d H:i:s');
    }

    protected $fillable = [
        'tanggal_pengeluaran',
        'nama_pengeluaran',
        'catatan_pengeluaran',
        'jumlah_pengeluaran',
        'kode_owner',
        'shift_id',
    ];
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class , 'sourceable');
    }
}
