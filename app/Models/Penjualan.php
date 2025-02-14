<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailSparepartPenjualan;

class Penjualan extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_penjualan',
        'kode_penjualan',
        'kode_owner',
        'nama_customer',
        'catatan_customer',
        'total_bayar',
        'total_penjualan',
        'user_input',
        'status_penjualan',
        'created_at'
    ];
    public function detailBarang()
    {
        return $this->hasMany(DetailBarangPenjualan::class, 'kode_penjualan', 'id');
    }

    public function detailSparepart()
    {
        return $this->hasMany(DetailSparepartPenjualan::class, 'kode_penjualan', 'id');
    }

}
