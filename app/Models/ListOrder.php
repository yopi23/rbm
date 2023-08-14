<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListOrder extends Model
{
    use HasFactory;
    protected $fillable = [
        'nama_order',
        'tgl_order',     
        'catatan_order',
        'user_input',
        'kode_owner',
    ];
}
