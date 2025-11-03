<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDetail extends Model
{
    protected $table = 'user_details';

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    use HasFactory;
    protected $fillable = [
        'kode_user',
        'foto_user',
        'fullname',
        'alamat_user',
        'no_telp',
        'jabatan',
        'id_upline',
        'saldo',
        'status_user',
        'kode_invite',
        'link_twitter',
        'link_facebook',
        'link_instagram',
        'link_linkedin',
        'qris_payload',
        'qris_payload',
        'macrodroid_secret',
        'face_embedding',
        'default_lat',
        'default_lon',
        'allowed_radius_m',
    ];

    public function withdrawals()
    {
        return $this->hasMany(Penarikan::class, 'kode_user', 'kode_user');
    }

    // Relationship untuk penarikan yang diproses oleh admin ini
    public function adminWithdrawals()
    {
        return $this->hasMany(Penarikan::class, 'admin_id', 'kode_user');
    }

    // Accessor untuk role text
    public function getRoleTextAttribute()
    {
        return match($this->jabatan) {
            '1' => 'Admin',
            '2' => 'Kasir',
            '3' => 'Teknisi',
            default => 'Unknown'
        };
    }

    // Scope untuk employees only (bukan admin)
    public function scopeEmployees($query)
    {
        return $query->whereIn('jabatan', ['2', '3']);
    }

}
