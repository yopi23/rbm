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
        'customer_id',
        'tgl_penjualan',
        'kode_penjualan',
        'kode_owner',
        'cabang_id',
        'nama_customer',
        'catatan_customer',
        'total_bayar',
        'metode_bayar',
        'jumlah_cash',
        'jumlah_transfer',
        'total_penjualan',
        'user_input',
        'status_penjualan',
        'shift_id',
        'created_at'
    ];
    protected static function booted()
    {
        static::addGlobalScope(new \App\Scopes\CabangScope);
    }

    public function customer()
    {
        return $this->belongsTo(customer_table::class, 'customer_id');
    }

    public function detailBarang()
    {
        return $this->hasMany(DetailBarangPenjualan::class , 'kode_penjualan', 'id');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function detailSparepart()
    {
        return $this->hasMany(DetailSparepartPenjualan::class , 'kode_penjualan', 'id');
    }
    public function kas()
    {
        return $this->morphMany(KasPerusahaan::class , 'sourceable');
    }
}
