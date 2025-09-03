<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BebanOperasional extends Model {
    use HasFactory;
    protected $table = 'beban_operasional';
    protected $fillable = ['kode_owner', 'nama_beban', 'jumlah_bulanan', 'keterangan'];
}
