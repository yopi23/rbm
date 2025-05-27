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
}








