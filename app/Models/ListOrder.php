<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ListOrder extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'order_id',
        'sparepart_id',
        'nama_item',
        'jumlah',
        'status_item',
        'user_input',
        'harga_perkiraan',
        'catatan_item',
        'kode_owner',
    ];

    /**
     * Relasi dengan Order
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Relasi dengan Sparepart
     */
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'sparepart_id');
    }

    /**
     * Mendapatkan subtotal dari item
     */
    public function getSubtotalAttribute()
    {
        if ($this->harga_perkiraan) {
            return $this->harga_perkiraan * $this->jumlah;
        }
        return 0;
    }

    /**
     * Mendapatkan status dalam format yang lebih mudah dibaca
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status_item) {
            case 'pending':
                return 'Menunggu';
            case 'dikirim':
                return 'Dikirim';
            case 'diterima':
                return 'Diterima';
            default:
                return 'Tidak Diketahui';
        }
    }
}
