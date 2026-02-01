<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    use HasFactory;
    protected $table = 'stock_history';

    protected $fillable = [
        'sparepart_id',
        'quantity_change',
        'reference_type',
        'reference_id',
        'stock_before',
        'stock_after',
        'notes',
        'user_input',
        'shift_id',
    ];

    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class);
    }
}
