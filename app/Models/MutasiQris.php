<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MutasiQris extends Model
{
    use HasFactory;

    protected $table = 'mutasi_qris';

    protected $fillable = [
        'owner_detail_id',
        'kasir_detail_id',
        'nominal',
        'keterangan',
        'status',
        'reported_at',
    ];

    // Hubungan ke Owner (optional, jika diperlukan di masa depan)
    public function owner()
    {
        return $this->belongsTo(UserDetail::class, 'owner_detail_id');
    }

    // Hubungan ke Kasir (optional)
    public function kasir()
    {
        return $this->belongsTo(UserDetail::class, 'kasir_detail_id');
    }
}
