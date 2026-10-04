<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Garansi extends Model
{
    use HasFactory;
    protected $fillable = [
        'type_garansi',
        'kode_garansi',
        'nama_garansi',
        'tgl_mulai_garansi',
        'tgl_exp_garansi',
        'catatan_garansi',
        'user_input',
        'kode_owner',
        'status_garansi',
    ];

    protected static function booted()
    {
        static::saved(function ($model) {
            if ($model->kode_owner) {
                \Illuminate\Support\Facades\Cache::increment("service_cache_version_{$model->kode_owner}");
            }
        });
        static::deleted(function ($model) {
            if ($model->kode_owner) {
                \Illuminate\Support\Facades\Cache::increment("service_cache_version_{$model->kode_owner}");
            }
        });
    }
}

