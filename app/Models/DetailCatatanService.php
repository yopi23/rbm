<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCatatanService extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_catatan_service',
        'kode_services',
        'kode_user',
        'catatan_service',
    ];

    protected static function booted()
    {
        $clearCache = function ($model) {
            $service = \App\Models\Sevices::find($model->kode_services);
            if ($service && $service->kode_owner) {
                \Illuminate\Support\Facades\Cache::increment("service_cache_version_{$service->kode_owner}");
            }
        };

        static::saved($clearCache);
        static::deleted($clearCache);
    }
}

