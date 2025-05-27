<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Violation extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'violation_date',
        'type',
        'description',
        'penalty_amount',
        'penalty_percentage',
        'status',
        'created_by'
    ];

    protected $casts = [
        'violation_date' => 'date',
        'penalty_amount' => 'decimal:2',
    ];

   public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
