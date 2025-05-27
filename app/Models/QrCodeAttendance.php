<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QrCodeAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'date',
        'type', // check_in atau check_out
        'created_by',
        'expires_at'
    ];

    protected $casts = [
        'date' => 'date',
        'expires_at' => 'datetime'
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
