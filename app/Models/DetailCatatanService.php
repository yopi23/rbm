<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCatatanService extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_catatan_service',
        'kode_services',
        'kode_user',
        'catatan_service',
    ];
}
