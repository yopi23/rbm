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
        'customer_id',
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
        'claimed_from_service_id',
        'status_services',
        'kode_owner',
        'shift_id',
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
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
    public function originalService()
    {
        return $this->belongsTo(Sevices::class, 'claimed_from_service_id');
    }

    /**
     * Mendapatkan semua service klaim yang berasal dari service ini.
     */
    public function claimedServices()
    {
        return $this->hasMany(Sevices::class, 'claimed_from_service_id');
    }

    public function variants()
{
    // Parameter ke-4 diubah menjadi 'kode_sparepart'
    return $this->belongsToMany(ProductVariant::class, 'detail_part_services', 'kode_services', 'kode_sparepart')
                ->withPivot('qty_part', 'jasa', 'harga_garansi', 'user_input')
                ->withTimestamps();
}

}
