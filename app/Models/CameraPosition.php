<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CameraPosition extends Model
{
    use HasFactory;
    protected $fillable = ['position', 'group'];

    public function hpDatas()
    {
        return $this->hasMany(HpData::class);
    }
}
