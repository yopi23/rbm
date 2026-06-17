<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'note',
        'photo_in',
        'photo_out',
        'location',
        'late_minutes',
        'created_by'
    ];

    protected $casts = [
        'attendance_date' => 'date',
        'check_in' => 'datetime:H:i:s',
        'check_out' => 'datetime:H:i:s',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // Calculate late minutes based on schedule
    public function calculateLateMinutes($schedule_start_time)
    {
        if (!$this->check_in) return 0;

        $check_in_time = \Carbon\Carbon::parse($this->check_in);
        $schedule_time = \Carbon\Carbon::parse($schedule_start_time);

        if ($check_in_time->gt($schedule_time)) {
            return $check_in_time->diffInMinutes($schedule_time);
        }

        return 0;
    }

    protected static function booted()
    {
        static::saved(function ($attendance) {
            if ($attendance->status === 'hadir' && $attendance->check_in) {
                // Check if user has a fixed compensation setting
                $salarySetting = SalarySetting::where('user_id', $attendance->user_id)->first();
                if ($salarySetting && $salarySetting->compensation_type === 'fixed') {
                    // Check if already created daily salary for this date
                    $exists = ProfitPresentase::where('kode_user', $attendance->user_id)
                        ->whereDate('tgl_profit', $attendance->attendance_date->toDateString())
                        ->where('kode_service', 0)
                        ->where('profit', '>', 0)
                        ->exists();

                    if (!$exists && $salarySetting->basic_salary > 0) {
                        ProfitPresentase::create([
                            'tgl_profit' => $attendance->attendance_date->toDateString(),
                            'kode_service' => 0,
                            'kode_presentase' => $salarySetting->id,
                            'kode_user' => $attendance->user_id,
                            'profit' => $salarySetting->basic_salary,
                            'profit_toko' => 0,
                            'is_cair' => 0, // Withheld
                        ]);
                    }
                }
            } else {
                // If status changed from 'hadir' to something else, remove the daily salary record
                ProfitPresentase::where('kode_user', $attendance->user_id)
                    ->whereDate('tgl_profit', $attendance->attendance_date->toDateString())
                    ->where('kode_service', 0)
                    ->where('profit', '>', 0)
                    ->delete();
            }
        });

        static::deleted(function ($attendance) {
            // Remove the daily salary record if attendance is deleted
            ProfitPresentase::where('kode_user', $attendance->user_id)
                ->whereDate('tgl_profit', $attendance->attendance_date->toDateString())
                ->where('kode_service', 0)
                ->where('profit', '>', 0)
                ->delete();
        });
    }
}








