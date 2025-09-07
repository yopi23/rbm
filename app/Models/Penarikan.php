<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Penarikan extends Model
{
    use HasFactory;

    protected $table = 'penarikans'; // Sesuai dengan tabel yang sudah ada

    protected $fillable = [
        'tgl_penarikan',
        'kode_penarikan',
        'kode_user',
        'kode_owner',
        'jumlah_penarikan',
        'catatan_penarikan',
        'status_penarikan',
        'dari_saldo',
        'admin_withdrawal',  // Tambahan baru
        'admin_id'          // Tambahan baru
    ];

    protected $casts = [
        'admin_withdrawal' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationship ke user yang melakukan penarikan (karyawan)
    public function employee()
    {
        return $this->belongsTo(UserDetail::class, 'kode_user', 'kode_user');
    }

    // Relationship ke admin yang melakukan penarikan (jika admin withdrawal)
    public function admin()
    {
        return $this->belongsTo(UserDetail::class, 'admin_id', 'kode_user');
    }

    // Relationship ke owner/upline
    public function owner()
    {
        return $this->belongsTo(UserDetail::class, 'kode_owner', 'kode_user');
    }

    // Scope untuk admin withdrawals
    public function scopeAdminWithdrawals($query)
    {
        return $query->where('admin_withdrawal', true);
    }

    // Scope untuk employee withdrawals
    public function scopeEmployeeWithdrawals($query)
    {
        return $query->where('admin_withdrawal', false);
    }

    // Accessor untuk status text
    public function getStatusTextAttribute()
    {
        if ($this->admin_withdrawal) {
            return 'Penarikan oleh Admin';
        }

        return match($this->status_penarikan) {
            '0' => 'Pending',
            '1' => 'Disetujui',
            '2' => 'Ditolak',
            default => 'Unknown'
        };
    }

    // Accessor untuk format jumlah
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->jumlah_penarikan, 0, ',', '.');
    }
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}

