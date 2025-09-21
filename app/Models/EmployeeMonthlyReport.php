<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmployeeMonthlyReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'year',
        'month',
        'total_service_units',
        'completed_units_not_taken',
        'taken_units',
        'total_service_amount',
        'total_commission',
        'total_shop_profit',
        'potential_shop_profit',
        'real_shop_profit',
        'total_claims_handled',
        'claims_from_own_work',
        'total_bonus',
        'total_penalties',
        'final_salary',
        'total_working_days',
        'total_present_days',
        'total_absent_days',
        'total_late_minutes',
        'status',
        'processed_by',
        'paid_at'
    ];

    protected $casts = [
        'total_service_amount' => 'decimal:2',
        'total_commission' => 'decimal:2',
        'total_bonus' => 'decimal:2',
        'total_penalties' => 'decimal:2',
        'final_salary' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
