<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];
    public function userDetail()
    {
        return $this->hasOne(UserDetail::class, 'kode_user');
    }
    // app/Models/User.php
    public function salarySetting()
    {
        return $this->hasOne(SalarySetting::class, 'user_id');
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class, 'user_id');
    }

    public function violations()
    {
        return $this->hasMany(Violation::class, 'user_id');
    }

    public function monthlyReports()
    {
        return $this->hasMany(EmployeeMonthlyReport::class, 'user_id');
    }

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class, 'user_id');
    }

}
