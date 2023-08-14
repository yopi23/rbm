<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RestokBarang extends Model
{
    use HasFactory;
    protected $fillable =[
       'kode_restok',
       'tgl_restok',
       'kode_barang',
       'jumlah_restok',
       'status_restok',
       'catatan_restok',
       'user_input',
       'kode_owner',
    ];
}
