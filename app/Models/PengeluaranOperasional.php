<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PengeluaranOperasional extends Model
{
    use HasFactory;
    protected $fillable = [
        'tgl_pengeluaran',
        'nama_pengeluaran',
        'kategori',
        'kode_pegawai',
        'jml_pengeluaran',
        'desc_pengeluaran',
        'kode_owner',
    ];

    protected $casts = [
        // 'tgl_pengeluaran' => 'date',
        'jml_pengeluaran' => 'integer',
        'kode_pegawai' => 'integer'
    ];

    /**
     * Get the owner that owns the pengeluaran operasional.
     */
    public function owner()
    {
        return $this->belongsTo(User::class, 'kode_owner', 'id');
    }

    /**
     * Get the employee associated with the pengeluaran operasional.
     */
    public function pegawai()
    {
        return $this->belongsTo(User::class, 'kode_pegawai', 'id');
    }

    /**
     * Scope untuk filter berdasarkan owner
     */
    public function scopeByOwner($query, $ownerId)
    {
        return $query->where('kode_owner', $ownerId);
    }

    /**
     * Scope untuk filter berdasarkan kategori
     */
    public function scopeByKategori($query, $kategori)
    {
        return $query->where('kategori', $kategori);
    }

    /**
     * Scope untuk filter berdasarkan tanggal
     */
    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('tgl_pengeluaran', [$startDate, $endDate]);
    }

    /**
     * Scope untuk pencarian
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function($q) use ($search) {
            $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
              ->orWhere('desc_pengeluaran', 'LIKE', "%{$search}%");
        });
    }



    /**
     * Accessor untuk format rupiah
     */
    public function getJmlPengeluaranFormattedAttribute()
    {
        return 'Rp. ' . number_format($this->jml_pengeluaran, 0, ',', '.') . ',-';
    }

    /**
     * Accessor untuk kategori display
     */
    public function getKategoriDisplayAttribute()
    {
        if ($this->kategori === 'Penggajian' && $this->pegawai) {
            return $this->kategori . ' (' . $this->pegawai->name . ')';
        }
        return $this->kategori;
    }

}
