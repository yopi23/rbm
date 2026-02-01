<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Hutang extends Model
{
    use HasFactory;
    // Nama tabel yang terkait dengan model ini
    protected $table = 'hutang';

    // Kolom yang dapat diisi melalui mass assignment
    protected $fillable = [
        'kode_supplier',
        'kode_owner',
        'kode_nota',
        'total_hutang',
        'status',
        'tgl_jatuh_tempo',
    ];
    public function supplier()
    {
        // Parameter:
        // 1. Supplier::class -> Model yang dituju.
        // 2. 'kode_supplier' -> Nama kolom foreign key di tabel 'hutang' ini.
        // 3. 'id' -> Nama kolom primary key di tabel 'suppliers' yang dituju.
        return $this->belongsTo(Supplier::class, 'kode_supplier', 'id');
    }
    public function pembelian()
    {
        // Asumsi kolom penghubungnya adalah:
        // Hutang.kode_nota -> Pembelian.kode_pembelian
        return $this->belongsTo(\App\Models\Pembelian::class, 'kode_nota', 'kode_pembelian');
    }

    /**
     * Relasi ke buku besar kas (polimorfik).
     */
    public function kas()
    {
        return $this->morphOne(KasPerusahaan::class, 'sourceable');
    }
}
