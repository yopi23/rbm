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
public function service()
    {
        // Parameter kedua adalah nama kolom foreign key di tabel 'detail_part_services'
        return $this->belongsTo(Sevices::class, 'kode_services');
    }

    /**
     * Mendapatkan data master sparepart dari detail part ini.
     * Laravel akan mencari foreign key 'sparepart_id'. Kita beritahu nama kolom yang benar.
     */
    public function sparepart()
    {
        // Parameter kedua adalah nama kolom foreign key di tabel 'detail_part_services'
        // Parameter ketiga adalah nama kolom primary key di tabel 'spareparts'
        return $this->belongsTo(Sparepart::class, 'kode_sparepart', 'id');
    }
}
