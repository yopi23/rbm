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

    /**
     * Record transaction to HistoryLaci with reference tracking
     *
     * @param int $kategoriId
     * @param float|null $uangMasuk
     * @param float|null $uangKeluar
     * @param string $keterangan
     * @param string|null $referenceType ('penarikan', 'penjualan', 'pembelian', 'manual', etc)
     * @param int|null $referenceId
     * @param string|null $referenceCode
     * @return HistoryLaci
     */
    public function recordLaciHistory(
        $kategoriId,
        $uangMasuk = null,
        $uangKeluar = null,
        $keterangan,
        $referenceType = null,
        $referenceId = null,
        $referenceCode = null
    ) {
        // Ambil kode owner dari user saat ini
        $kodeOwner = $this->getThisUser()->id_upline;

        // Buat record baru di HistoryLaci
        return HistoryLaci::create([
            'kode_owner' => $kodeOwner,
            'id_kategori' => $kategoriId,
            'masuk' => $uangMasuk,
            'keluar' => $uangKeluar,
            'keterangan' => $keterangan,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'reference_code' => $referenceCode,
        ]);
    }

    /**
     * Record penarikan to laci history
     *
     * @param int $kategoriId
     * @param float $jumlah
     * @param string $keterangan
     * @param int $pengambilanId
     * @param string $kodePengambilan
     * @return HistoryLaci
     */
    public function recordPenarikanLaci($kategoriId, $jumlah, $keterangan, $pengambilanId, $kodePengambilan)
    {
        return $this->recordLaciHistory(
            $kategoriId,
            null, // uang masuk
            $jumlah, // uang keluar
            $keterangan,
            'penarikan',
            $pengambilanId,
            $kodePengambilan
        );
    }

    /**
     * Record penjualan to laci history
     *
     * @param int $kategoriId
     * @param float $jumlah
     * @param string $keterangan
     * @param int $penjualanId
     * @param string $kodePenjualan
     * @return HistoryLaci
     */
    public function recordPenjualanLaci($kategoriId, $jumlah, $keterangan, $penjualanId, $kodePenjualan)
    {
        return $this->recordLaciHistory(
            $kategoriId,
            $jumlah, // uang masuk
            null, // uang keluar
            $keterangan,
            'penjualan',
            $penjualanId,
            $kodePenjualan
        );
    }

    /**
     * Record pembelian to laci history
     *
     * @param int $kategoriId
     * @param float $jumlah
     * @param string $keterangan
     * @param int $pembelianId
     * @param string $kodePembelian
     * @return HistoryLaci
     */
    public function recordPembelianLaci($kategoriId, $jumlah, $keterangan, $pembelianId, $kodePembelian)
    {
        return $this->recordLaciHistory(
            $kategoriId,
            null, // uang masuk
            $jumlah, // uang keluar
            $keterangan,
            'pembelian',
            $pembelianId,
            $kodePembelian
        );
    }

    /**
     * Get history laci by reference
     *
     * @param string $referenceType
     * @param int $referenceId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoryByReference($referenceType, $referenceId)
    {
        return HistoryLaci::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['reference_type', '=', $referenceType],
            ['reference_id', '=', $referenceId]
        ])->get();
    }

    /**
     * Get history laci by reference code
     *
     * @param string $referenceCode
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getHistoryByReferenceCode($referenceCode)
    {
        return HistoryLaci::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['reference_code', '=', $referenceCode]
        ])->get();
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
