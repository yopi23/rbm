<?php

namespace App\Traits;

use App\Models\Aset;
use App\Models\BebanOperasional;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\PemasukkanLain;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\ProfitPresentase;
use App\Models\Sevices;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

trait ProfitCalculationTrait
{
    /**
     * Menghitung laba bersih untuk owner tertentu dalam rentang tanggal tertentu.
     * Mengembalikan array berisi laba kotor, total beban, laba bersih, dan rincian beban.
     *
     * @param int $kode_owner
     * @param string $tgl_awal (format YYYY-MM-DD)
     * @param string $tgl_akhir (format YYYY-MM-DD)
     * @return array
     */
    protected function calculateNetProfit(int $kode_owner, string $tgl_awal, string $tgl_akhir): array
    {
        $startRange = Carbon::parse($tgl_awal)->startOfDay();
        $endRange = Carbon::parse($tgl_akhir)->endOfDay();

        // Variabel kunci untuk prorata
        $calculatedJumlahHariPeriode = $startRange->diffInDays($endRange) + 1;
        $calculatedDaysInMonth = $startRange->daysInMonth;
        $calculatedDaysInYear = $startRange->daysInYear;

        // dd([
        //     'kode_owner' => $kode_owner,
        //     'tgl_awal' => $tgl_awal,
        //     'tgl_akhir' => $tgl_akhir,
        //     'startRange' => $startRange->toDateString(),
        //     'endRange' => $endRange->toDateString(),
        //     'calculatedJumlahHariPeriode' => $calculatedJumlahHariPeriode, // Seharusnya 24
        //     'calculatedDaysInMonth' => $calculatedDaysInMonth,           // Seharusnya 30
        //     'calculatedDaysInYear' => $calculatedDaysInYear,             // Seharusnya 365
        // ]);


        // ==============================
        // A.1 & A.2: Perhitungan Pendapatan (Sudah Cocok)
        // ==============================
        $totalPendapatanPenjualan = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('updated_at', [$startRange, $endRange])
            ->sum('total_penjualan');
        $totalPendapatanService = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange, $endRange])
            ->sum('total_biaya');
        $totalPemasukkanLain = PemasukkanLain::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$startRange, $endRange])
            ->sum('jumlah_pemasukkan');

        $totalPendapatan = $totalPendapatanPenjualan + $totalPendapatanService + $totalPemasukkanLain;

        // ==============================
        // B.1 & B.2: Perhitungan HPP (Sudah Cocok)
        // ==============================
        $penjualanIds = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppSparepartJual = DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualanIds)
            ->sum(DB::raw('detail_harga_modal * qty_sparepart'));
        $hppBarangJual = DetailBarangPenjualan::whereIn('kode_penjualan', $penjualanIds)
            ->sum(DB::raw('detail_harga_modal * qty_barang'));

        $serviceIdsDiambil = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppPartTokoService = DetailPartServices::whereIn('kode_services', $serviceIdsDiambil)
            ->sum(DB::raw('detail_modal_part_service * qty_part'));
        $hppPartLuarService = DetailPartLuarService::whereIn('kode_services', $serviceIdsDiambil)
            ->sum(DB::raw('harga_part * qty_part'));

        $totalHpp = $hppSparepartJual + $hppBarangJual + $hppPartTokoService + $hppPartLuarService;

        // ==============================
        // C. Hitung Laba Kotor (Sudah Cocok)
        // ==============================
        $labaKotor = $totalPendapatan - $totalHpp;

        // ==============================
        // D.1 & D.2: Biaya Variabel (Sudah Cocok)
        // ==============================
        $biayaOperasionalInsidental = PengeluaranToko::where('kode_owner', $kode_owner)
            ->whereBetween('tanggal_pengeluaran', [$startRange, $endRange])
            ->sum('jumlah_pengeluaran');

        $serviceIdsSelesai = Sevices::where('kode_owner', $kode_owner)
            ->whereIn('status_services', ['Selesai', 'Diambil'])
            ->whereBetween('tgl_service', [$startRange, $endRange])->pluck('id');
        $biayaKomisi = ProfitPresentase::whereIn('kode_service', $serviceIdsSelesai)->sum('profit');

        // ======================================================================
        // E.1 Beban dari Aset Tetap (Penyusutan) - KRITIS DI SINI
        // ======================================================================
        $totalPenyusutanBulananAktif = Aset::where('kode_owner', $kode_owner)
            ->where('tanggal_perolehan', '<=', $endRange->toDateString()) // Filter aset yang sudah aktif
            ->sum(DB::raw('(nilai_perolehan - nilai_residu) / masa_manfaat_bulan'));

        // dd([
        //     'totalPenyusutanBulananAktif' => $totalPenyusutanBulananAktif, // Seharusnya 197916.67
        //     'calculatedDaysInMonth' => $calculatedDaysInMonth,             // Seharusnya 30
        //     'calculatedJumlahHariPeriode' => $calculatedJumlahHariPeriode, // Seharusnya 24
        // ]);

        $bebanPenyusutanHarian = ($calculatedDaysInMonth > 0) ? $totalPenyusutanBulananAktif / $calculatedDaysInMonth : 0;
        $bebanPenyusutanPeriodik = $bebanPenyusutanHarian * $calculatedJumlahHariPeriode;

        // dd([
        //     'bebanPenyusutanHarian' => $bebanPenyusutanHarian,          // Seharusnya 6597.22
        //     'bebanPenyusutanPeriodik' => $bebanPenyusutanPeriodik,      // Seharusnya 158333.34
        // ]);


        // ======================================================================
        // E.2 Beban dari Operasional Tetap (Bulanan & Tahunan) - KRITIS DI SINI
        // ======================================================================
        $totalBebanBulananAktif = BebanOperasional::where('kode_owner', $kode_owner)
            ->where('periode', 'bulanan')
            // Menambahkan filter created_at agar hanya beban yang sudah aktif di atau sebelum periode laporan
            ->where('created_at', '<=', $endRange)
            ->sum('nominal');

        $totalBebanTahunanAktif = BebanOperasional::where('kode_owner', $kode_owner)
            ->where('periode', 'tahunan')
            // Menambahkan filter created_at agar hanya beban yang sudah aktif di atau sebelum periode laporan
            ->where('created_at', '<=', $endRange)
            ->sum('nominal');

        // dd([
        //     'totalBebanBulananAktif' => $totalBebanBulananAktif,         // Seharusnya 300000
        //     'totalBebanTahunanAktif' => $totalBebanTahunanAktif,         // Seharusnya 18750000
        //     'calculatedDaysInMonth' => $calculatedDaysInMonth,           // Seharusnya 30
        //     'calculatedDaysInYear' => $calculatedDaysInYear,             // Seharusnya 365
        //     'calculatedJumlahHariPeriode' => $calculatedJumlahHariPeriode, // Seharusnya 24
        // ]);


        $bebanHarianDariBulanan = ($calculatedDaysInMonth > 0) ? $totalBebanBulananAktif / $calculatedDaysInMonth : 0;
        $bebanHarianDariTahunan = ($calculatedDaysInYear > 0) ? $totalBebanTahunanAktif / $calculatedDaysInYear : 0;

        $totalBebanTetapHarian = $bebanHarianDariBulanan + $bebanHarianDariTahunan;
        $bebanTetapPeriodik = $totalBebanTetapHarian * $calculatedJumlahHariPeriode;

        // dd([
        //     'bebanHarianDariBulanan' => $bebanHarianDariBulanan,        // Seharusnya 10000
        //     'bebanHarianDariTahunan' => $bebanHarianDariTahunan,        // Seharusnya 51369.86
        //     'totalBebanTetapHarian' => $totalBebanTetapHarian,          // Seharusnya 61369.86
        //     'bebanTetapPeriodik' => $bebanTetapPeriodik,                // Seharusnya 1472876.64
        // ]);


        // ======================================================================

        // F. Hitung Laba Bersih Final
        $labaBersih = $labaKotor - $biayaOperasionalInsidental - $biayaKomisi - $bebanPenyusutanPeriodik - $bebanTetapPeriodik;

        // Buat array detail beban untuk laporan
        $detailBeban = [
            'HPP (Modal Pokok Penjualan)' => $totalHpp,
            'Biaya Operasional Insidental' => $biayaOperasionalInsidental,
            'Biaya Komisi Teknisi' => $biayaKomisi,
            'Beban Penyusutan Aset' => $bebanPenyusutanPeriodik,
            'Beban Tetap Periodik' => $bebanTetapPeriodik,
        ];

        // Rounding semua nilai di akhir agar konsisten dan menghindari presisi float
        return [
            'laba_kotor' => round($labaKotor, 0),
            'total_beban' => round(array_sum($detailBeban), 0),
            'laba_bersih' => round($labaBersih, 0),
            'detail_beban' => array_map(function($value) { return round($value, 0); }, $detailBeban),
            'jumlah_hari_periode' => $calculatedJumlahHariPeriode,
            'hari_dalam_bulan_awal_periode' => $calculatedDaysInMonth,
            'hari_dalam_tahun_awal_periode' => $calculatedDaysInYear,
        ];
    }
}
