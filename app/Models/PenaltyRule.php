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
        }

        return $query->first();
    }

    // NEW: Method khusus untuk absence rules
    public static function findApplicableRuleForAbsence($compensationType, $ownerCode)
    {
        if (!$ownerCode) {
            return null;
        }

        // Cari rule untuk absence dengan compensation type yang spesifik
        $rule = static::where('rule_type', 'absence')
            ->where('compensation_type', $compensationType)
            ->where('is_active', true)
            ->forOwner($ownerCode)
            ->orderBy('priority', 'asc')
            ->first();

        // Jika tidak ada, cari rule 'both'
        if (!$rule) {
            $rule = static::where('rule_type', 'absence')
                ->where('compensation_type', 'both')
                ->where('is_active', true)
                ->forOwner($ownerCode)
                ->orderBy('priority', 'asc')
                ->first();
        }

        return $rule;
    }

    // UPDATED: getDefaultRules dengan parameter ownerCode dan tambahan absence rules
    public static function getDefaultRules($ownerCode)
{
    $defaultRules = [
        // ========== ATTENDANCE LATE RULES ==========
        // Fixed Salary Rules
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'fixed',
            'min_minutes' => 1,
            'max_minutes' => 15,
            'penalty_amount' => 5000,
            'penalty_percentage' => 0,
            'description' => 'Keterlambatan ringan (1-15 menit) - Denda Rp 10.000',
            'priority' => 1,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'fixed',
            'min_minutes' => 16,
            'max_minutes' => 30,
            'penalty_amount' => 10000,
            'penalty_percentage' => 0,
            'description' => 'Keterlambatan sedang (16-30 menit) - Denda Rp 25.000',
            'priority' => 2,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'fixed',
            'min_minutes' => 31,
            'max_minutes' => 60,
            'penalty_amount' => 25000,
            'penalty_percentage' => 0,
            'description' => 'Keterlambatan berat (31-60 menit) - Denda Rp 50.000',
            'priority' => 3,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'fixed',
            'min_minutes' => 61,
            'max_minutes' => null,
            'penalty_amount' => 50000,
            'penalty_percentage' => 0,
            'description' => 'Keterlambatan sangat berat (>60 menit) - Denda Rp 100.000',
            'priority' => 4,
            'created_by' => auth()->id()
        ],

        // Percentage Salary Rules
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 1,
            'max_minutes' => 15,
            'penalty_amount' => 0,
            'penalty_percentage' => 1,
            'description' => 'Keterlambatan ringan (1-15 menit) - Penalty 2%',
            'priority' => 1,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 16,
            'max_minutes' => 30,
            'penalty_amount' => 0,
            'penalty_percentage' => 2,
            'description' => 'Keterlambatan sedang (16-30 menit) - Penalty 5%',
            'priority' => 2,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 31,
            'max_minutes' => 60,
            'penalty_amount' => 0,
            'penalty_percentage' => 5,
            'description' => 'Keterlambatan berat (31-60 menit) - Penalty 10%',
            'priority' => 3,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'attendance_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 61,
            'max_minutes' => null,
            'penalty_amount' => 0,
            'penalty_percentage' => 10,
            'description' => 'Keterlambatan sangat berat (>60 menit) - Penalty 20%',
            'priority' => 4,
            'created_by' => auth()->id()
        ],

        // ========== OUTSIDE OFFICE LATE RULES ==========
        // Menambahkan aturan untuk keterlambatan 1-15 menit untuk menutup celah waktu.
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'outside_office_late',
            'compensation_type' => 'percentage', // Diubah dari 'both' menjadi 'percentage'.
            'min_minutes' => 1,
            'max_minutes' => 15,
            'penalty_amount' => 0,
            'penalty_percentage' => 0, // Tidak ada penalti
            'description' => 'Terlambat kembali sangat ringan (1-15 menit) - Tidak ada penalti',
            'priority' => 1,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'outside_office_late',
            'compensation_type' => 'percentage', // Diubah dari 'both' menjadi 'percentage' agar konsisten.
            'min_minutes' => 16,
            'max_minutes' => 30,
            'penalty_amount' => 0,
            'penalty_percentage' => 2,
            'description' => 'Terlambat kembali ringan (16-30 menit) - Penalty 2%',
            'priority' => 2, // Prioritas disesuaikan.
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'outside_office_late',
            'compensation_type' => 'percentage', // Diubah dari 'both' menjadi 'percentage'.
            'min_minutes' => 31,
            'max_minutes' => 60,
            'penalty_amount' => 0,
            'penalty_percentage' => 5,
            'description' => 'Terlambat kembali sedang (31-60 menit) - Penalty 5%',
            'priority' => 3, // Prioritas disesuaikan.
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'outside_office_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 61,
            'max_minutes' => 120,
            'penalty_amount' => 0,
            'penalty_percentage' => 10,
            'description' => 'Terlambat kembali berat (1-2 jam) - Penalty 10%',
            'priority' => 4,
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'outside_office_late',
            'compensation_type' => 'percentage',
            'min_minutes' => 121,
            'max_minutes' => null,
            'penalty_amount' => 0,
            'penalty_percentage' => 15,
            'description' => 'Terlambat kembali sangat berat (>2 jam) - Penalty 15%',
            'priority' => 5,
            'created_by' => auth()->id()
        ],

        // ========== ABSENCE/ALPHA RULES ==========
        // Aturan dibuat unik berdasarkan prioritas dan compensation_type dibuat konsisten.
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'absence',
            'compensation_type' => 'fixed', // Diubah dari 'fixed' karena logikanya adalah persentase (100% dari gaji harian).
            'min_minutes' => 0,
            'max_minutes' => null,
            'penalty_amount' => 50000,
            'penalty_percentage' => 0, // Penalti 100% (dari gaji harian).
            'description' => 'Alpha (Tipe Gaji Tetap) - Potong 100% gaji ',
            'priority' => 1, // Aturan utama untuk alpha
            'created_by' => auth()->id()
        ],
        [
            'kode_owner' => $ownerCode,
            'rule_type' => 'absence',
            'compensation_type' => 'percentage',
            'min_minutes' => 0,
            'max_minutes' => null,
            'penalty_amount' => 0,
            'penalty_percentage' => 5,
            'description' => 'Alpha (Tipe Gaji Presentanse) - Denda tambahan 5%',
            'priority' => 1, // Prioritas diubah menjadi 2 untuk menghindari konflik.
            'created_by' => auth()->id()
        ]
    ];

    return $defaultRules;
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
