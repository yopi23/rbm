<?php

namespace App\Traits;

use App\Models\User;
use App\Models\KategoriLaci;
use App\Models\HistoryLaci;
use App\Models\Pengambilan;
use App\Models\Sevices as modelServices;

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
    public function getOrCreatePengambilan()
    {
        $data = Pengambilan::where([
            ['user_input', '=', auth()->user()->id],
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_pengambilan', '=', '0']
        ])->first();

        $count = Pengambilan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline]
        ])->count();

        if (!$data) {
            $kode_pengambilan = 'PNG' . date('Ymd') . auth()->user()->id . $count;
            $create = Pengambilan::create([
                'kode_pengambilan' => $kode_pengambilan,
                'tgl_pengambilan' => date('Y-m-d'),
                'nama_pengambilan' => '',
                'total_bayar' => '0',
                'user_input' => auth()->user()->id,
                'status_pengambilan' => '0',
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);
            if ($create) {
                $data = Pengambilan::where([
                    ['user_input', '=', auth()->user()->id],
                    ['kode_owner', '=', $this->getThisUser()->id_upline],
                    ['status_pengambilan', '=', '0']
                ])->first();
            }
        }

        return $data;
    }

    public function getServices($pengambilanId)
    {
        $pengambilanServices = modelServices::where([
            ['kode_pengambilan', '=', $pengambilanId],
            ['status_services', '=', 'Selesai'],
            ['kode_owner', '=', $this->getThisUser()->id_upline]
        ])->get();

        $done_service = modelServices::where([
            ['status_services', '=', 'Selesai'],
            ['kode_owner', '=', $this->getThisUser()->id_upline]
        ])->get();

        return compact('pengambilanServices', 'done_service');
    }
}
