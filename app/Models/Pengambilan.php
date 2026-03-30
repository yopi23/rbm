<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Pengambilan extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_pengambilan',
        'tgl_pengambilan',
        'nama_pengambilan',
        'total_bayar',
        'total_services',
        'dp',
        'metode_bayar',
        'jumlah_cash',
        'jumlah_transfer',
        'kode_owner',
        'user_input',
        'status_pengambilan',
        'shift_id',
    ];
    public function kas()
    {
        return $this->morphMany(KasPerusahaan::class , 'sourceable');
    }
}
