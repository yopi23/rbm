<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $table = 'order'; // Pastikan ini sesuai dengan nama tabel Anda
    protected $fillable = [
        'kode_order',
        'spl_kode',
        'kode_owner',
        'status_order',
    ];

    public function detailOrders()
    {
        return $this->hasMany(DetailOrder::class, 'id_order', 'id');
    }
}
