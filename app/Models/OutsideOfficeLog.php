<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OutsideOfficeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'log_date',
        'start_time',
        'end_time',
        'actual_return_time',
        'reason',
        'status',
        'late_return_minutes',
        'approval_status',
        'approved_by',
        'created_by',
        'violation_note'
    ];

    protected $casts = [
        'log_date' => 'date',
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'actual_return_time' => 'datetime'
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeToday($query)
    {
        return $query->whereDate('log_date', Carbon::today());
    }

    public function scopeThisMonth($query, $year = null, $month = null)
    {
        $year = $year ?? Carbon::now()->year;
        $month = $month ?? Carbon::now()->month;

        return $query->whereYear('log_date', $year)
                    ->whereMonth('log_date', $month);
    }

    // Accessors & Mutators
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status !== 'active' || !$this->end_time) {
            return false;
        }

        return Carbon::now()->greaterThan($this->end_time);
    }

    public function getFormattedDurationAttribute(): string
    {
        if (!$this->start_time || !$this->end_time) {
            return '-';
        }

        $duration = $this->start_time->diff($this->end_time);
        return $duration->format('%H jam %I menit');
    }

    // Methods
    public function markAsReturned(): bool
    {
        $now = Carbon::now();
        $lateMinutes = 0;

        if ($this->end_time && $now->greaterThan($this->end_time)) {
            $lateMinutes = $now->diffInMinutes($this->end_time);
        }

        $this->update([
            'actual_return_time' => $now,
            'late_return_minutes' => $lateMinutes,
            'status' => $lateMinutes > 0 ? 'violated' : 'completed'
        ]);

        // Buat violation jika terlambat lebih dari 15 menit
        if ($lateMinutes > 15) {
            $this->createViolationRecord($lateMinutes);
        }

        return true;
    }

    private function createViolationRecord(int $lateMinutes): void
    {
        Violation::create([
            'user_id' => $this->user_id,
            'violation_date' => $this->log_date,
            'type' => 'kelalaian',
            'description' => "Terlambat kembali dari izin keluar: {$lateMinutes} menit (Izin: {$this->reason})",
            'penalty_percentage' => $this->calculatePenaltyPercentage($lateMinutes),
            'status' => 'pending',
            'created_by' => 1, // System
        ]);
    }

    private function calculatePenaltyPercentage(int $lateMinutes): int
    {
        // Penalty bertingkat berdasarkan keterlambatan
        if ($lateMinutes <= 30) return 2;      // 2% untuk 15-30 menit
        if ($lateMinutes <= 60) return 5;      // 5% untuk 30-60 menit
        if ($lateMinutes <= 120) return 10;    // 10% untuk 1-2 jam
        return 15;                             // 15% untuk lebih dari 2 jam
    }

    public function approve(int $approvedBy): bool
    {
        return $this->update([
            'approval_status' => 'approved',
            'approved_by' => $approvedBy
        ]);
    }

    public function reject(int $approvedBy, string $reason = null): bool
    {
        return $this->update([
            'approval_status' => 'rejected',
            'approved_by' => $approvedBy,
            'violation_note' => $reason,
            'status' => 'violated'
        ]);
    }
}
