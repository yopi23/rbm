<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'report_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penjualans()
    {
        return $this->hasMany(Penjualan::class, 'shift_id');
    }

    public function services()
    {
        return $this->hasMany(Sevices::class, 'shift_id');
    }

    public function pengeluaranTokos()
    {
        return $this->hasMany(PengeluaranToko::class, 'shift_id');
    }

    public function pengeluaranOperasionals()
    {
        return $this->hasMany(PengeluaranOperasional::class, 'shift_id');
    }

    public function pembelians()
    {
        return $this->hasMany(Pembelian::class, 'shift_id');
    }

    public function kasPerusahaan()
    {
        return $this->hasMany(KasPerusahaan::class, 'shift_id');
    }

    public static function getActiveShift($userId)
    {
        // Cari detail user untuk menentukan Owner/Store context
        $userDetail = UserDetail::where('kode_user', $userId)->first();

        if (!$userDetail) {
            // Fallback jika detail tidak ditemukan (misal user baru/error data)
            return self::where('user_id', $userId)
                ->where('status', 'open')
                ->latest()
                ->first();
        }

        // Tentukan ID Owner (Store)
        // Jika user adalah owner (jabatan 1), gunakan ID mereka sendiri.
        // Jika pegawai, gunakan id_upline mereka.
        $ownerId = ($userDetail->jabatan == '1') ? $userId : $userDetail->id_upline;

        // Cari shift aktif untuk Owner/Store ini (Shared Shift)
        return self::where('kode_owner', $ownerId)
            ->where('status', 'open')
            ->latest()
            ->first();
    }
}
