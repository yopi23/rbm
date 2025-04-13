<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StockOpnamePeriod extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_periode',
        'nama_periode',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'catatan',
        'user_input',
        'kode_owner',
    ];

    protected $casts = [
        'tanggal_mulai' => 'date',
        'tanggal_selesai' => 'date',
    ];

    // Relasi dengan detail stock opname
    public function details()
    {
        return $this->hasMany(StockOpnameDetail::class, 'period_id');
    }

    // Relasi dengan user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_input');
    }

    // Accessor untuk status dalam format yang lebih mudah dibaca
    public function getStatusTextAttribute()
    {
        switch ($this->status) {
            case 'draft':
                return 'Draft';
            case 'in_progress':
                return 'Sedang Berjalan';
            case 'completed':
                return 'Selesai';
            case 'cancelled':
                return 'Dibatalkan';
            default:
                return 'Tidak Diketahui';
        }
    }

    // Accessor untuk mendapatkan progres opname
    public function getProgressAttribute()
    {
        $totalItems = $this->details()->count();
        $checkedItems = $this->details()->where('status', '!=', 'pending')->count();

        if ($totalItems === 0) {
            return 0;
        }

        return round(($checkedItems / $totalItems) * 100);
    }

    // Accessor untuk mendapatkan jumlah item yang belum dicek
    public function getPendingItemsCountAttribute()
    {
        return $this->details()->where('status', 'pending')->count();
    }

    // Accessor untuk mendapatkan jumlah item yang sudah dicek
    public function getCheckedItemsCountAttribute()
    {
        return $this->details()->where('status', 'checked')->count();
    }

    // Accessor untuk mendapatkan jumlah item yang sudah disesuaikan
    public function getAdjustedItemsCountAttribute()
    {
        return $this->details()->where('status', 'adjusted')->count();
    }

    // Accessor untuk mendapatkan total selisih (positif)
    public function getTotalPositiveSelisihAttribute()
    {
        return $this->details()
            ->where('selisih', '>', 0)
            ->sum('selisih');
    }

    // Accessor untuk mendapatkan total selisih (negatif)
    public function getTotalNegativeSelisihAttribute()
    {
        return $this->details()
            ->where('selisih', '<', 0)
            ->sum('selisih');
    }
}
