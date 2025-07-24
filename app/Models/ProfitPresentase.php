<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfitPresentase extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_profit',
        'kode_service',
        'kode_presentase',
        'kode_user',
        'profit',
        'saldo',
        'profit_toko',
    ];
}
