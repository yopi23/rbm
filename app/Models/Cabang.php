<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cabang extends Model
{
    use HasFactory;

    protected $table = 'cabangs';

    protected $fillable = [
        'kode_owner',
        'nama_cabang',
        'alamat_cabang',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'kode_owner');
    }

    public function users()
    {
        return $this->hasMany(User::class, 'cabang_id');
    }

    public function shifts()
    {
        return $this->hasMany(Shift::class, 'cabang_id');
    }

    public function jurnals()
    {
        return $this->hasMany(JurnalHarianCabang::class, 'cabang_id');
    }
}
