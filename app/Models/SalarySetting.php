<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySetting extends Model
{
    use HasFactory;

    protected $table = 'salary_settings';

    protected $fillable = [
        'user_id',
        'compensation_type',
        'basic_salary',
        'service_percentage',
        'target_bonus',
        'monthly_target',
        'percentage_value',
        'created_by',
        'is_active'
    ];

    protected $casts = [
        'basic_salary' => 'decimal:2',
        'service_percentage' => 'integer',
        'target_bonus' => 'decimal:2',
        'monthly_target' => 'integer',
        'percentage_value' => 'decimal:2',
        'is_active' => 'boolean'
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
