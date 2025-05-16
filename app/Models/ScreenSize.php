<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScreenSize extends Model
{
    use HasFactory;
    protected $fillable = ['size'];

    public function hpDatas()
    {
        return $this->hasMany(HpData::class);
    }
}
