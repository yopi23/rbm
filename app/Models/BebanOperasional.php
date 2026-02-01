<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BebanOperasional extends Model
{
    use HasFactory;

    protected $table = 'beban_operasional';

    protected $fillable = [
        'kode_owner',
        'nama_beban',
        'periode', // 'bulanan' or 'tahunan'
        'nominal',
        'current_balance', // Dana terkumpul (Sinking Fund)
        'is_active',
        'keterangan',
    ];

    /**
     * Relasi ke pengeluaran realisasi.
     */
    public function pengeluaranOperasional()
    {
        return $this->hasMany(PengeluaranOperasional::class);
    }
}
