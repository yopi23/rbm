<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockNotification extends Model
{
    use HasFactory;
    protected $fillable = [
        'sparepart_id',
        'current_stock',
        'reorder_point',
        'reorder_quantity',
        'status',
        'created_by',
        'processed_by',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }
}
