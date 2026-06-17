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
        'applied_penalty_amount',
        'status',
        'processed_at',
        'processed_by',
        'applied_at',
        'reversal_reason',
        'reversed_at',
        'reversed_by',
        'created_by',
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

    protected static function booted()
    {
        static::creating(function ($violation) {
            // Automatically process violations upon creation
            if (empty($violation->status) || $violation->status === 'pending') {
                $violation->status = 'processed';
            }
            if (empty($violation->processed_at)) {
                $violation->processed_at = now();
            }
            if (empty($violation->processed_by) && auth()->check()) {
                $violation->processed_by = auth()->id();
            }
        });

        static::created(function ($violation) {
            if ($violation->status === 'processed') {
                $violation->applyPenalty();
            }
        });
    }

    public function applyPenalty()
    {
        $salarySetting = SalarySetting::where('user_id', $this->user_id)->first();

        if (!$salarySetting) {
            return;
        }

        $penaltyAmount = 0;

        if ($salarySetting->compensation_type === 'fixed') {
            if ($this->penalty_amount > 0) {
                $penaltyAmount = $this->penalty_amount;
            } elseif ($this->penalty_percentage > 0) {
                $penaltyAmount = ($salarySetting->basic_salary * $this->penalty_percentage) / 100;
            }

            if ($penaltyAmount > 0) {
                // Check if already created a negative profit presentase for this violation
                $exists = ProfitPresentase::where('kode_user', $this->user_id)
                    ->whereDate('tgl_profit', $this->violation_date->toDateString())
                    ->where('kode_service', 0)
                    ->where('profit', '<', 0)
                    ->exists();

                if (!$exists) {
                    ProfitPresentase::create([
                        'tgl_profit' => $this->violation_date->toDateString(),
                        'kode_service' => 0,
                        'kode_presentase' => $salarySetting->id,
                        'kode_user' => $this->user_id,
                        'profit' => -$penaltyAmount,
                        'profit_toko' => 0,
                        'is_cair' => 0, // Withheld
                    ]);
                }
            }
        } else { // 'percentage' or 'tiered'
            // We no longer permanently update the percentage value in salary settings.
            // Instead, we just calculate the estimated penalty amount for logging/records.
            if ($this->penalty_percentage > 0) {
                $lastMonth = \Carbon\Carbon::now()->subMonth();
                $startDate = $lastMonth->startOfMonth();
                $endDate = $lastMonth->endOfMonth();

                $services = \App\Models\Sevices::where('id_teknisi', $this->user_id)
                    ->whereIn('status_services', ['Selesai', 'Diambil'])
                    ->whereBetween('updated_at', [$startDate, $endDate])
                    ->get();

                if (!$services->isEmpty()) {
                    $serviceIds = $services->pluck('id');
                    $totalProfit = \App\Models\ProfitPresentase::whereIn('kode_service', $serviceIds)
                        ->where('kode_user', $this->user_id)
                        ->sum(\DB::raw('profit + profit_toko'));
                    
                    $penaltyAmount = ($totalProfit * $this->penalty_percentage) / 100;
                }
            } elseif ($this->penalty_amount > 0) {
                $penaltyAmount = $this->penalty_amount;
            }
        }

        \DB::table('violations')
            ->where('id', $this->id)
            ->update([
                'applied_penalty_amount' => $penaltyAmount,
                'applied_at' => now()
            ]);
    }
}
