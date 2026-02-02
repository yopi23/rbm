<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReturSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_retur_barang',
        'kode_supplier',
        'kode_barang',
        'jumlah_retur',
        'status_retur',
        'catatan_retur',
        'user_input',
        'kode_owner',
        'shift_id',
    ];
}
