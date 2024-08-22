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
    ];
}
