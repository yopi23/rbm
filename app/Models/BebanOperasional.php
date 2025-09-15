<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BebanOperasional extends Model {
    use HasFactory;
    protected $table = 'beban_operasional';
    protected $fillable = [
        'nama_beban',
        'periode',
        'nominal',
        'keterangan',
        'kode_owner',
];

     public function pengeluaranOperasional()
    {
        return $this->hasMany(PengeluaranOperasional::class);
    }
}
