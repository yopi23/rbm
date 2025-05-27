<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'basic_salary',
        'service_percentage',
        'target_bonus',
        'monthly_target',
        'is_active',
        'created_by'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'basic_salary' => 'decimal:2',
        'target_bonus' => 'decimal:2',
    ];

    // app/Models/SalarySetting.php
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
