<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailPartLuarService extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_services',
        'nama_part',
        'service_job_id',
        'harga_part',
        'qty_part',
        'is_potong_kas',
        'is_tanggungan_teknisi',
        'user_input',
    ];

    public function job()
    {
        return $this->belongsTo(ServiceJob::class, 'service_job_id');
    }

    public function service()
    {
        return $this->belongsTo(Sevices::class, 'kode_services');
    }
}
