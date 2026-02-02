<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestokSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_restok',
        'kode_supplier',
        'kode_owner',
        'tgl_restok',
        'kode_barang',
        'jumlah_restok',
        'status_restok',
        'catatan_restok',
        'user_input',
        'kode_owner',
        'shift_id',
    ];
}
