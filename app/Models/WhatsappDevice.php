<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WhatsappDevice extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'session_id',
        'api_key',
        'status',
        'phone_number',
        'qr_code_endpoint'
    ];
}
