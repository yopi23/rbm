<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use App\Models\UserDetail;
use App\Models\Subscription;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
    /**
     * Mendapatkan data langganan milik user ini.
     */
    public function subscription(): HasOne
    {
        return $this->hasOne(Subscription::class);
    }

    /**
     * Mendapatkan riwayat pembayaran dari user ini.
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Fungsi utama untuk mengecek apakah user (atau owner-nya)
     * memiliki langganan aktif.
     */
    public function hasActiveSubscription(): bool
    {
        // Pastikan relasi userDetail ada untuk menghindari error
        if (!$this->userDetail) {
            return false;
        }

        // Jabatan 0 (Super Admin) selalu dianggap aktif
        if ($this->userDetail->jabatan == '0') {
            return true;
        }

        // Jabatan 1 (Admin/Owner) cek langganan miliknya sendiri
        if ($this->userDetail->jabatan == '1') {
            $subscription = $this->subscription;
            // Cek apakah langganan ada, statusnya aktif, dan belum expired
            return $subscription && $subscription->status === 'active' && $subscription->expires_at->isFuture();
        }

        // Untuk jabatan lain (karyawan), cek status langganan upline-nya
        $owner = User::find($this->userDetail->id_upline);
        if ($owner) {
            // Memanggil fungsi yang sama pada data owner-nya (rekursif)
            return $owner->hasActiveSubscription();
        }

        // Jika tidak punya owner, maka tidak aktif
        return false;
    }

}
