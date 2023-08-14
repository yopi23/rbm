<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PresentaseUser extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_user',
        'presentase'
    ];
}
