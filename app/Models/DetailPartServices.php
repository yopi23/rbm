<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPartServices extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_services',
        'kode_sparepart',
        'detail_modal_part_service',
        'detail_harga_part_service',
        'qty_part',
        'user_input',
    ];
}
