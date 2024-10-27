<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailOrder extends Model
{
    use HasFactory;
    protected $table = 'detail_order'; // Pastikan ini sesuai dengan nama tabel Anda
    protected $fillable = [
        'id_order',
        'id_barang',
        'id_pesanan',
        'id_kategori',
        'nama_barang',
        'qty',
        'beli_terakhir',
        'pasang_terakhir',
        'ecer_terakhir',
        'jasa_terakhir',
    ];

    public function order()
    {
        return $this->belongsTo(Order::class, 'id_order', 'id');
    }
}
