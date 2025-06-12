<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenaltyRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'rule_type',
        'compensation_type',
        'min_minutes',
        'max_minutes',
        'penalty_amount',
        'penalty_percentage',
        'description',
        'is_active',
        'priority',
        'metadata',
        'kode_owner',
        'created_by',
        'updated_by'
    ];

    protected $casts = [
        'penalty_amount' => 'decimal:2',
        'is_active' => 'boolean',
        'metadata' => 'array'
    ];

    // Relationships
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function scopeForOwner($query, $ownerCode)
{
    return $query->where('kode_owner', $ownerCode);
}

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForRuleType($query, $ruleType)
    {
        return $query->where('rule_type', $ruleType);
    }

    public function scopeForCompensationType($query, $compensationType)
    {
        return $query->where('compensation_type', $compensationType)
                    ->orWhere('compensation_type', 'both');
    }

    public function scopeOrderedByPriority($query)
    {
        return $query->orderBy('priority')->orderBy('min_minutes');
    }

    // Static methods
    public static function findApplicableRule($ruleType, $compensationType, $minutes, $ownerCode)
    {

        $query = static::active()
            ->forRuleType($ruleType)
            ->forCompensationType($compensationType)
            ->where('min_minutes', '<=', $minutes)
            ->where(function($query) use ($minutes) {
                $query->whereNull('max_minutes')
                    ->orWhere('max_minutes', '>=', $minutes);
            })
            ->orderedByPriority();

        if ($ownerCode) {
            $query->where('kode_owner', $ownerCode);
        } else {
            $query->forCurrentOwner();
        }

        return $query->first();
    }

    public static function getDefaultRules()
    {
        return [
            // Attendance Late Rules - Fixed Salary
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'fixed',
                'min_minutes' => 6,
                'max_minutes' => 15,
                'penalty_amount' => 10000,
                'penalty_percentage' => 0,
                'description' => 'Keterlambatan ringan (6-15 menit) - Denda Rp 10.000',
                'priority' => 1
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'fixed',
                'min_minutes' => 16,
                'max_minutes' => 30,
                'penalty_amount' => 25000,
                'penalty_percentage' => 0,
                'description' => 'Keterlambatan sedang (16-30 menit) - Denda Rp 25.000',
                'priority' => 2
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'fixed',
                'min_minutes' => 31,
                'max_minutes' => 60,
                'penalty_amount' => 50000,
                'penalty_percentage' => 0,
                'description' => 'Keterlambatan berat (31-60 menit) - Denda Rp 50.000',
                'priority' => 3
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'fixed',
                'min_minutes' => 61,
                'max_minutes' => 120,
                'penalty_amount' => 100000,
                'penalty_percentage' => 0,
                'description' => 'Keterlambatan sangat berat (1-2 jam) - Denda Rp 100.000',
                'priority' => 4
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'fixed',
                'min_minutes' => 121,
                'max_minutes' => null,
                'penalty_amount' => 200000,
                'penalty_percentage' => 0,
                'description' => 'Keterlambatan ekstrem (>2 jam) - Denda Rp 200.000',
                'priority' => 5
            ],

            // Attendance Late Rules - Percentage Salary
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'percentage',
                'min_minutes' => 6,
                'max_minutes' => 15,
                'penalty_amount' => 0,
                'penalty_percentage' => 1,
                'description' => 'Keterlambatan ringan (6-15 menit) - Penalty 1%',
                'priority' => 1
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'percentage',
                'min_minutes' => 16,
                'max_minutes' => 30,
                'penalty_amount' => 0,
                'penalty_percentage' => 2,
                'description' => 'Keterlambatan sedang (16-30 menit) - Penalty 2%',
                'priority' => 2
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'percentage',
                'min_minutes' => 31,
                'max_minutes' => 60,
                'penalty_amount' => 0,
                'penalty_percentage' => 5,
                'description' => 'Keterlambatan berat (31-60 menit) - Penalty 5%',
                'priority' => 3
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'percentage',
                'min_minutes' => 61,
                'max_minutes' => 120,
                'penalty_amount' => 0,
                'penalty_percentage' => 10,
                'description' => 'Keterlambatan sangat berat (1-2 jam) - Penalty 10%',
                'priority' => 4
            ],
            [
                'rule_type' => 'attendance_late',
                'compensation_type' => 'percentage',
                'min_minutes' => 121,
                'max_minutes' => null,
                'penalty_amount' => 0,
                'penalty_percentage' => 15,
                'description' => 'Keterlambatan ekstrem (>2 jam) - Penalty 15%',
                'priority' => 5
            ],

            // Outside Office Late Rules
            [
                'rule_type' => 'outside_office_late',
                'compensation_type' => 'both',
                'min_minutes' => 16,
                'max_minutes' => 30,
                'penalty_amount' => 0,
                'penalty_percentage' => 1,
                'description' => 'Terlambat kembali 15-30 menit - Penalty 1%',
                'priority' => 1
            ],
            [
                'rule_type' => 'outside_office_late',
                'compensation_type' => 'both',
                'min_minutes' => 31,
                'max_minutes' => 60,
                'penalty_amount' => 0,
                'penalty_percentage' => 2,
                'description' => 'Terlambat kembali 30-60 menit - Penalty 2%',
                'priority' => 2
            ],
            [
                'rule_type' => 'outside_office_late',
                'compensation_type' => 'both',
                'min_minutes' => 61,
                'max_minutes' => 120,
                'penalty_amount' => 0,
                'penalty_percentage' => 5,
                'description' => 'Terlambat kembali 1-2 jam - Penalty 5%',
                'priority' => 3
            ],
            [
                'rule_type' => 'outside_office_late',
                'compensation_type' => 'both',
                'min_minutes' => 121,
                'max_minutes' => null,
                'penalty_amount' => 0,
                'penalty_percentage' => 10,
                'description' => 'Terlambat kembali >2 jam - Penalty 10%',
                'priority' => 4
            ]
        ];
    }

    // Helper methods
    public function getFormattedPenaltyAttribute()
    {
        if ($this->penalty_amount > 0) {
            return 'Rp ' . number_format($this->penalty_amount, 0, ',', '.');
        } else {
            return $this->penalty_percentage . '%';
        }
    }

    public function getRangeDescriptionAttribute()
    {
        $min = $this->min_minutes;
        $max = $this->max_minutes;

        if ($max === null) {
            return ">{$min} menit";
        } else {
            return "{$min}-{$max} menit";
        }
    }
}
