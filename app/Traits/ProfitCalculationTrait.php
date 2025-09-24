<?php
// app/Traits/ProfitCalculationTrait.php

namespace App\Traits;

use App\Models\Penjualan;
use App\Models\Sevices;
use App\Models\DetailSparepartPenjualan;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailPartServices;
use App\Models\DetailPartLuarService;
use App\Models\PengeluaranToko;
use App\Models\ProfitPresentase;
use App\Models\Aset;
use App\Models\BebanOperasional;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

trait ProfitCalculationTrait
{
    /**
     * FUNGSI UTAMA & TERPUSAT UNTUK MENGHITUNG LABA BERSIH SECARA AKURAT
     *
     * @param int $kode_owner
     * @param string $tanggalMulai
     * @param string $tanggalSelesai
     * @return array
     */
    public function calculateNetProfit(int $kode_owner, string $tanggalMulai, string $tanggalSelesai): array
    {
        $startRange = Carbon::parse($tanggalMulai)->startOfDay();
        $endRange = Carbon::parse($tanggalSelesai)->endOfDay();

        // 1. Hitung Pendapatan
        $totalPendapatanPenjualan = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')->whereBetween('updated_at', [$startRange, $endRange])->sum('total_penjualan');
        $totalPendapatanService = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')->whereBetween('updated_at', [$startRange, $endRange])->sum('total_biaya');
        $totalPendapatan = $totalPendapatanPenjualan + $totalPendapatanService;

        // 2. Hitung Harga Pokok Penjualan (HPP)
        $penjualanIds = Penjualan::where('kode_owner', $kode_owner)->where('status_penjualan', '1')
            ->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppSparepartJual = DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_sparepart'));
        $hppBarangJual = DetailBarangPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_barang'));

        $serviceIdsDiambil = Sevices::where('kode_owner', $kode_owner)->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppPartTokoService = DetailPartServices::whereIn('kode_services', $serviceIdsDiambil)->sum(DB::raw('detail_modal_part_service * qty_part'));
        $hppPartLuarService = DetailPartLuarService::whereIn('kode_services', $serviceIdsDiambil)->sum(DB::raw('harga_part * qty_part'));
        $totalHpp = $hppSparepartJual + $hppBarangJual + $hppPartTokoService + $hppPartLuarService;

        // 3. Hitung Laba Kotor
        $labaKotor = $totalPendapatan - $totalHpp;

        // 4. Hitung Biaya Variabel
        $biayaOperasionalInsidental = PengeluaranToko::where('kode_owner', $kode_owner)
            ->whereBetween('tanggal_pengeluaran', [$startRange, $endRange])->sum('jumlah_pengeluaran');

        $serviceIdsSelesai = Sevices::where('kode_owner', $kode_owner)->whereIn('status_services', ['Selesai', 'Diambil'])
            ->whereBetween('tgl_service', [$startRange, $endRange])->pluck('id');
        $biayaKomisi = ProfitPresentase::whereIn('kode_service', $serviceIdsSelesai)->sum('profit');

        // 5. Hitung Beban Tetap Periodik
        $jumlahHariPeriode = $startRange->diffInDays($endRange) + 1;

        // Beban Penyusutan Aset
        $totalPenyusutanBulanan = Aset::where('kode_owner', $kode_owner)->sum(DB::raw('(nilai_perolehan - nilai_residu) / masa_manfaat_bulan'));
        $bebanPenyusutanHarian = ($startRange->daysInMonth > 0) ? $totalPenyusutanBulanan / $startRange->daysInMonth : 0;
        $bebanPenyusutanPeriodik = $bebanPenyusutanHarian * $jumlahHariPeriode;

        // Beban Operasional Tetap
        $totalBebanBulanan = BebanOperasional::where('kode_owner', $kode_owner)->where('periode', 'bulanan')->sum('nominal');
        $totalBebanTahunan = BebanOperasional::where('kode_owner', $kode_owner)->where('periode', 'tahunan')->sum('nominal');
        $bebanHarianDariBulanan = ($startRange->daysInMonth > 0) ? $totalBebanBulanan / $startRange->daysInMonth : 0;
        $bebanHarianDariTahunan = ($startRange->daysInYear > 0) ? $totalBebanTahunan / $startRange->daysInYear : 0;
        $bebanTetapPeriodik = ($bebanHarianDariBulanan + $bebanHarianDariTahunan) * $jumlahHariPeriode;

        // 6. Hitung Laba Bersih Final
        $labaBersih = $labaKotor - $biayaOperasionalInsidental - $biayaKomisi - $bebanPenyusutanPeriodik - $bebanTetapPeriodik;

        return [
            'laba_bersih' => $labaBersih,
            'laba_kotor' => $labaKotor,
            'total_pendapatan' => $totalPendapatan,
            'detail_beban' => [
                'HPP' => $totalHpp,
                'Biaya Operasional Insidental' => $biayaOperasionalInsidental,
                'Biaya Komisi Teknisi' => $biayaKomisi,
                'Beban Penyusutan Aset' => $bebanPenyusutanPeriodik,
                'Beban Tetap Periodik' => $bebanTetapPeriodik,
            ],
            'total_beban' => $totalHpp + $biayaOperasionalInsidental + $biayaKomisi + $bebanPenyusutanPeriodik + $bebanTetapPeriodik
        ];
    }
}
