<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HpData extends Model
{
    use HasFactory;
    protected $table = 'hp_datas';
    protected $fillable = ['brand_id', 'type', 'screen_size_id', 'camera_position_id'];

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }

    public function screenSize()
    {
        return $this->belongsTo(ScreenSize::class);
    }

    public function cameraPosition()
    {
        return $this->belongsTo(CameraPosition::class);
    }
}
