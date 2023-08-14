<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPartLuarService extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_services',
        'nama_part',
        'harga_part',
        'qty_part',
        'user_input',
    ];
}
