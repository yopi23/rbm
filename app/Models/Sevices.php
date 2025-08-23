<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Garansi;
use App\Models\DetailCatatanService;
use App\Models\DetailPartServices;
use App\Models\DetailPartLuarService;

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

    public function teknisi()
    {
        return $this->belongsTo(User::class, 'id_teknisi');
    }

    public function garansi()
    {
        return $this->hasMany(Garansi::class, 'kode_garansi', 'kode_service')
                    ->where('type_garansi', 'service');
    }

    public function catatan()
    {
        return $this->hasMany(DetailCatatanService::class, 'kode_services', 'id');
    }

    public function partToko()
    {
        return $this->hasMany(DetailPartServices::class, 'kode_services', 'id');
    }

    public function partLuar()
    {
        return $this->hasMany(DetailPartLuarService::class, 'kode_services', 'id');
    }
}
