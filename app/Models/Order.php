<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'kode_order',
        'tanggal_order',
        'tanggal_kirim_perkiraan',
        'kode_supplier',
        'status_order',
        'catatan',
        'total_item',
        'user_input',
        'kode_owner',
    ];

    /**
     * Relasi dengan ListOrder (detail pesanan)
     */
    public function listOrders()
    {
        return $this->hasMany(ListOrder::class, 'order_id');
    }

    /**
     * Relasi dengan Supplier
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kode_supplier');
    }

    /**
     * Relasi dengan User (pembuat pesanan)
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_input');
    }

    /**
     * Mendapatkan total harga perkiraan dari pesanan
     */
    public function getTotalHargaPerkiraanAttribute()
    {
        $total = 0;
        foreach ($this->listOrders as $item) {
            if ($item->harga_perkiraan) {
                $total += ($item->harga_perkiraan * $item->jumlah);
            }
        }
        return $total;
    }

    /**
     * Mendapatkan status dalam format yang lebih mudah dibaca
     */
    public function getStatusTextAttribute()
    {
        switch ($this->status_order) {
            case 'draft':
                return 'Draft';
            case 'menunggu_pengiriman':
                return 'Menunggu Pengiriman';
            case 'selesai':
                return 'Selesai';
            case 'dibatalkan':
                return 'Dibatalkan';
            default:
                return 'Tidak Diketahui';
        }
    }
}
