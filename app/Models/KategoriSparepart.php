<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriSparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'foto_kategori',
        'nama_kategori',
        'kode_owner',
        'is_active',
    ];

    public function spareparts()
    {
        return $this->hasMany(Sparepart::class, 'kode_kategori', 'kode_kategori');
    }
    public function subKategori()
    {
        return $this->hasMany(SubKategoriSparepart::class, 'kategori_id');
    }

    public function priceSetting()
    {
        return $this->hasOne(PriceSetting::class);
    }

    public function attributes()
    {
        return $this->hasMany(Attribute::class);
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
