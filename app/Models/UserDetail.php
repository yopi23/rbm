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
    ];

}
