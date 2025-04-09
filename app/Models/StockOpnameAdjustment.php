<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameAdjustment extends Model
{
    use HasFactory;
    protected $fillable = [
        'detail_id',
        'stock_before',
        'stock_after',
        'adjustment_qty',
        'alasan_adjustment',
        'user_input',
        'kode_owner',
    ];

    // Relasi dengan detail opname
    public function detail()
    {
        return $this->belongsTo(StockOpnameDetail::class, 'detail_id');
    }

    // Relasi dengan user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_input');
    }

    // Accessor untuk menampilkan jenis penyesuaian (penambahan/pengurangan)
    public function getAdjustmentTypeAttribute()
    {
        if ($this->adjustment_qty > 0) {
            return 'Penambahan';
        } elseif ($this->adjustment_qty < 0) {
            return 'Pengurangan';
        } else {
            return 'Tidak Ada Perubahan';
        }
    }

    // Accessor untuk badge class
    public function getAdjustmentBadgeClassAttribute()
    {
        if ($this->adjustment_qty > 0) {
            return 'badge-success';
        } elseif ($this->adjustment_qty < 0) {
            return 'badge-danger';
        } else {
            return 'badge-info';
        }
    }
}
