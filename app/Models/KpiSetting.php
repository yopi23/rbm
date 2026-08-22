<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KpiSetting extends Model
{
    use HasFactory;

    protected $table = 'kpi_settings';

    protected $fillable = [
        'kode_owner',
        'weight_productivity',
        'weight_quality',
        'weight_rework',
        'weight_discipline',
        'weight_sop',
        'bonus_scheme',
        'pool_percentage',
        'min_kpi_bonus',
        'tier_rules',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'weight_productivity' => 'decimal:2',
        'weight_quality' => 'decimal:2',
        'weight_rework' => 'decimal:2',
        'weight_discipline' => 'decimal:2',
        'weight_sop' => 'decimal:2',
        'pool_percentage' => 'decimal:2',
        'min_kpi_bonus' => 'decimal:2',
        'tier_rules' => 'array',
        'is_active' => 'boolean',
    ];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForOwner($query, $ownerCode)
    {
        return $query->where('kode_owner', $ownerCode);
    }

    /**
     * Aturan default tier bonus sesuai panduan manajemen
     */
    public static function getDefaultTierRules(): array
    {
        return [
            ['min_score' => 100.00, 'max_score' => 999.00, 'bonus_amount' => 1000000, 'label' => '> 100% (Istimewa)'],
            ['min_score' => 95.00,  'max_score' => 99.99,  'bonus_amount' => 750000,  'label' => '95% - 100% (Sangat Baik)'],
            ['min_score' => 90.00,  'max_score' => 94.99,  'bonus_amount' => 500000,  'label' => '90% - 94% (Baik)'],
            ['min_score' => 80.00,  'max_score' => 89.99,  'bonus_amount' => 250000,  'label' => '80% - 89% (Cukup)'],
            ['min_score' => 70.00,  'max_score' => 79.99,  'bonus_amount' => 100000,  'label' => '70% - 79% (Standar)'],
            ['min_score' => 0.00,   'max_score' => 69.99,  'bonus_amount' => 0,       'label' => '< 70% (Perlu Pembinaan)'],
        ];
    }

    /**
     * Dapatkan setting aktif untuk owner atau buat default jika belum ada
     */
    public static function getEffectiveSettings($ownerCode)
    {
        if (empty($ownerCode)) {
            $ownerCode = '0';
        }

        $setting = static::where('kode_owner', $ownerCode)->first();

        if (!$setting) {
            $setting = static::create([
                'kode_owner' => (string) $ownerCode,
                'weight_productivity' => 30.00,
                'weight_quality' => 30.00,
                'weight_rework' => 20.00,
                'weight_discipline' => 10.00,
                'weight_sop' => 10.00,
                'bonus_scheme' => 'hybrid',
                'pool_percentage' => 10.00,
                'min_kpi_bonus' => 70.00,
                'tier_rules' => static::getDefaultTierRules(),
                'is_active' => true,
                'created_by' => auth()->id() ?? 1,
            ]);
        }

        return $setting;
    }

    /**
     * Hitung nominal bonus tier berdasarkan skor KPI
     */
    public function calculateTierBonus(float $kpiScore): float
    {
        if ($kpiScore < (float) $this->min_kpi_bonus) {
            return 0.0;
        }

        $rules = is_array($this->tier_rules) ? $this->tier_rules : static::getDefaultTierRules();

        foreach ($rules as $rule) {
            $min = (float) ($rule['min_score'] ?? 0);
            $max = (float) ($rule['max_score'] ?? 999);
            if ($kpiScore >= $min && $kpiScore <= $max) {
                return (float) ($rule['bonus_amount'] ?? 0);
            }
        }

        return 0.0;
    }
}
