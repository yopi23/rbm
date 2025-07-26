<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaKhusus extends Model
{
    use HasFactory;
    protected $table = 'harga_khususes';
    protected $fillable = [
        'id_sp',
        'harga_toko',
        'harga_satuan',
        'diskon_tipe',
        'diskon_nilai',
    ];

    // Accessor untuk harga akhir
    public function getHargaAkhirAttribute()
    {
        $harga = $this->harga_satuan;

        if ($this->diskon_tipe === 'persentase') {
            return $harga - ($harga * $this->diskon_nilai / 100);
        } elseif ($this->diskon_tipe === 'potongan') {
            return $harga - $this->diskon_nilai;
        }

        return $harga;
    }
    public function sparepart()
    {
        return $this->belongsTo(Sparepart::class, 'id_sp', 'id');
    }
}
