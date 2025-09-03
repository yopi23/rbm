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
        'kode_owner',
        'user_input',
        'status_pengambilan',
    ];
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}
