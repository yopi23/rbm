<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Penarikan extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_penarikan',
        'kode_penarikan',
        'kode_user',
        'kode_owner',
        'jumlah_penarikan',
        'catatan_penarikan',
        'status_penarikan',
        'dari_saldo'
    ];
}
