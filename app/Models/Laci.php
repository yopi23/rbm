<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Laci extends Model
{
    use HasFactory;

    protected $table = 'laci';

    protected $fillable = [
        'user_id',
        'kode_owner',
        'receh',
        'real',
        'tanggal'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
