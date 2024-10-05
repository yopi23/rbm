<?php

namespace App\Traits;

use App\Models\User;
use App\Models\KategoriLaci;
use App\Models\HistoryLaci;

trait KategoriLaciTrait
{
    public function getKategoriLaci()
    {
        return KategoriLaci::where('kode_owner', $this->getThisUser()->id_upline)->get();
    }

    public function recordLaciHistory($kategoriId, $uangMasuk = null, $uangKeluar = null, $keterangan)
    {
        // Ambil kode owner dari user saat ini
        $kodeOwner = $this->getThisUser()->id_upline;

        // Buat record baru di HistoryLaci
        HistoryLaci::create([
            'kode_owner' => $kodeOwner,
            'id_kategori' => $kategoriId,
            'masuk' => $uangMasuk,
            'keluar' => $uangKeluar,
            'keterangan' => $keterangan,
        ]);
    }
}
