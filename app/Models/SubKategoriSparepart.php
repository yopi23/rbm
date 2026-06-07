<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubKategoriSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'kategori_id',
        'nama_sub_kategori',
        'foto_sub_kategori',
        'kode_owner'
    ];

    /**
     * Get the parent category that owns the subcategory.
     */
    public function kategori()
    {
        return $this->belongsTo(KategoriSparepart::class, 'kategori_id');
    }

    /**
     * Get the spareparts for the subcategory.
     */
    public function spareparts()
    {
        return $this->hasMany(Sparepart::class, 'kode_sub_kategori');
    }

    protected static $findCache = [];

    /**
     * Static helper with request-level memoization to avoid redundant database lookups
     */
    public static function findCached($id)
    {
        if (empty($id)) return null;
        if (!isset(self::$findCache[$id])) {
            self::$findCache[$id] = self::find($id);
        }
        return self::$findCache[$id];
    }
}
