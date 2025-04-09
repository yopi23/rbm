<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockOpnameDetail extends Model
{
    use HasFactory;
    protected $fillable = [
        'period_id',
        'sparepart_id',
        'stock_tercatat',
        'stock_aktual',
        'selisih',
        'status',
        'catatan',
        'user_check',
        'checked_at',
        'kode_owner',
    ];

    protected $casts = [
        'checked_at' => 'datetime',
    ];

    // Relasi dengan periode opname
    public function period()
    {
        return $this->belongsTo(StockOpnamePeriod::class, 'period_id');
    }

    // Relasi dengan sparepart
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'sparepart_id');
    }

    // Relasi dengan user yang melakukan pengecekan
    public function userCheck()
    {
        return $this->belongsTo(User::class, 'user_check');
    }

    // Relasi dengan riwayat penyesuaian
    public function adjustments()
    {
        return $this->hasMany(StockOpnameAdjustment::class, 'detail_id');
    }

    // Accessor untuk status dalam format yang lebih mudah dibaca
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'pending':
                return 'Belum Diperiksa';
            case 'checked':
                return 'Sudah Diperiksa';
            case 'adjusted':
                return 'Sudah Disesuaikan';
            default:
                return 'Tidak Diketahui';
        }
    }

    // Accessor untuk menentukan apakah ada selisih
    public function getHasSelisihAttribute()
    {
        return $this->selisih !== 0 && $this->selisih !== null;
    }

    // Accessor untuk mendapatkan badge class berdasarkan selisih
    public function getSelisihBadgeClassAttribute()
    {
        if ($this->selisih === null) {
            return 'badge-secondary';
        } elseif ($this->selisih > 0) {
            return 'badge-success';
        } elseif ($this->selisih < 0) {
            return 'badge-danger';
        } else {
            return 'badge-info';
        }
    }
}
