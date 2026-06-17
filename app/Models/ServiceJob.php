<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceJob extends Model
{
    use HasFactory;

    protected $fillable = [
        'service_id',
        'service_category_id',
        'nama_pekerjaan',
        'biaya_jasa',
    ];

    public function service()
    {
        return $this->belongsTo(Sevices::class, 'service_id');
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'service_category_id');
    }

    public function partsToko()
    {
        return $this->hasMany(DetailPartServices::class, 'service_job_id');
    }

    public function partsLuar()
    {
        return $this->hasMany(DetailPartLuarService::class, 'service_job_id');
    }
}
