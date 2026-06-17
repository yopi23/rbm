<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JurnalHarianCabang extends Model
{
    use HasFactory;

    protected $table = 'jurnal_harian_cabangs';

    protected $fillable = [
        'cabang_id',
        'shift_id',
        'tanggal',
        'omset_tunai',
        'omset_non_tunai',
        'hpp_terjual',
        'biaya_operasional_lokal',
        'komisi_teknisi',
        'kas_seharusnya_disetor',
        'kas_aktual_disetor',
    ];

    protected $casts = [
        'tanggal' => 'date',
        'omset_tunai' => 'float',
        'omset_non_tunai' => 'float',
        'hpp_terjual' => 'float',
        'biaya_operasional_lokal' => 'float',
        'komisi_teknisi' => 'float',
        'kas_seharusnya_disetor' => 'float',
        'kas_aktual_disetor' => 'float',
    ];

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    public function shift()
    {
        return $this->belongsTo(Shift::class, 'shift_id');
    }
}
