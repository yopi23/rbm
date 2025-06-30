<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranToko extends Model
{
    use HasFactory;
    protected $table = 'pengeluaran_tokos';

    protected $fillable = [
        'tanggal_pengeluaran',
        'nama_pengeluaran',
        'jumlah_pengeluaran',
        'catatan_pengeluaran',
        'kode_owner'
    ];

    protected $casts = [
        // tanggal_pengeluaran disimpan sebagai string di database
        // jumlah_pengeluaran disimpan sebagai string di database
    ];

    /**
     * Get the owner that owns the pengeluaran toko.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'kode_owner', 'id');
    }

    /**
     * Scope untuk filter berdasarkan owner
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('kode_owner', $ownerId);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);
    }

    /**
     * Scope untuk pencarian
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
              ->orWhere('catatan_pengeluaran', 'LIKE', "%{$search}%");
        });
    }

    /**
     * Accessor untuk format rupiah
     */
    public function getJumlahPengeluaranFormattedAttribute()
    {
        $cleanAmount = (int) str_replace(',', '', $this->jumlah_pengeluaran);
        return 'Rp. ' . number_format($cleanAmount, 0, ',', '.') . ',-';
    }

    /**
     * Accessor untuk mendapatkan nilai integer dari jumlah pengeluaran
     */
    public function getJumlahPengeluaranIntAttribute()
    {
        return (int) str_replace(',', '', $this->jumlah_pengeluaran);
    }
}

