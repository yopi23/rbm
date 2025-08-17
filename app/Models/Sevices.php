<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sevices extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_service',
        'tgl_service',
        'nama_pelanggan',
        'no_telp',
        'type_unit',
        'keterangan',
        'tipe_sandi',
        'isi_sandi',
        'data_unit' ,
        'total_biaya',
        'dp',
        'harga_sp',
        'id_teknisi',
        'kode_pengambilan',
        'status_services',
        'kode_owner',
        'created_at',
    ];
}
