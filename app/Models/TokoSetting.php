<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TokoSetting extends Model
{
    use HasFactory;
    protected $fillable = [
        'id_owner',
        'nama_toko',
        'alamat_toko',
        'nomor_cs',
        'nomor_info_bot',
        'nota_footer_line1',
        'nota_footer_line2',
        'logo_url',
    ];
}
