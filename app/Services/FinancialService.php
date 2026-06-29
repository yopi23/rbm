<?php

namespace App\Services;

use App\Models\Aset;
use App\Models\BebanOperasional;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\DistribusiLaba;
use App\Models\KasPerusahaan;
use App\Models\PemasukkanLain;
use App\Models\Pembelian;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\ProfitPresentase;
use App\Models\Sevices;
use App\Models\TransaksiModal;
use App\Models\Cabang;
use App\Models\JurnalHarianCabang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FinancialService
{
    private array $nonRevenueSourceTypes = [
        'App\\Models\\TransaksiModal',
        'App\\Models\\PenerimaanHutang',
    ];

    private array $nonExpenseSourceTypes = [
        'App\\Models\\Pembelian',
        'App\\Models\\TransaksiModal',
        'App\\Models\\DistribusiLaba',
        'App\\Models\\PembayaranHutang',
        'App\\Models\\AlokasiLaba',
        'App\\Models\\Penarikan', // Penarikan Owner
    ];

    /**
     * Menghitung Laba Rugi Bersih (Net Profit)
     * Mengadopsi logika dari ProfitCalculationTrait namun diperbarui
     * untuk fleksibilitas rentang tanggal dan akurasi HPP.
     * 
     * @param int $ownerId
     * @param Carbon|string $startDate
     * @param Carbon|string $endDate
     * @return array
     */
    public function calculateNetProfit($ownerId, $startDate, $endDate, $cabangId = null)
    {
        $startRange = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endRange = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        
        $startRange = $startRange->copy()->startOfDay();
        $endRange = $endRange->copy()->endOfDay();

        // Variabel kunci untuk prorata
        $calculatedJumlahHariPeriode = $startRange->diffInDays($endRange) + 1;
        $calculatedDaysInMonth = $startRange->daysInMonth;

        // Query Jurnal Harian Cabang
        $query = JurnalHarianCabang::whereBetween('tanggal', [$startRange->toDateString(), $endRange->toDateString()]);

        if ($cabangId) {
            $query->where('cabang_id', $cabangId);
        } else {
            $query->whereHas('cabang', function ($q) use ($ownerId) {
                $q->where('kode_owner', $ownerId);
            });
        }

        $jurnalSummary = $query->selectRaw('
            SUM(omset_tunai) as total_omset_tunai,
            SUM(omset_non_tunai) as total_omset_non_tunai,
            SUM(hpp_terjual) as total_hpp,
            SUM(biaya_operasional_lokal) as total_biaya_operasional_lokal,
            SUM(komisi_teknisi) as total_komisi_teknisi
        ')->first();

        // Query PemasukkanLain to exclude titipan and include modal_pemasukan
        $pemasukanQuery = PemasukkanLain::where('kode_owner', $ownerId)
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()])
            ->whereHas('shift', function ($q) {
                $q->where('status', 'closed');
            });
        if ($cabangId) {
            $pemasukanQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        $pemasukanData = $pemasukanQuery->selectRaw('
            SUM(CASE WHEN sifat_pemasukan = "titipan" THEN jumlah_pemasukkan ELSE 0 END) as total_titipan,
            SUM(CASE WHEN sifat_pemasukan = "pendapatan" THEN modal_pemasukan ELSE 0 END) as total_modal_pendapatan
        ')->first();

        $totalTitipan = $pemasukanData->total_titipan ?? 0;
        $totalModalPendapatan = $pemasukanData->total_modal_pendapatan ?? 0;

        $revenue = ($jurnalSummary->total_omset_tunai ?? 0) + ($jurnalSummary->total_omset_non_tunai ?? 0);
        $revenue -= $totalTitipan;

        $hpp = ($jurnalSummary->total_hpp ?? 0) + $totalModalPendapatan;
        
        // LABA KOTOR
        $grossProfit = $revenue - $hpp;

        // EXPENSES (Beban-beban)
        $biayaOperasionalLokal = $jurnalSummary->total_biaya_operasional_lokal ?? 0;
        $biayaKomisi = $jurnalSummary->total_komisi_teknisi ?? 0;

        // C. Beban Penyusutan Aset (Depreciation) - Dihitung berdasarkan alokasi Cabang jika cabangId dispesifikasikan
        $queryAset = Aset::where('kode_owner', $ownerId)
            ->where('tanggal_perolehan', '<=', $endRange->toDateString());
        if ($cabangId) {
            $queryAset->where('cabang_id', $cabangId);
        }
        $totalPenyusutanBulananAktif = $queryAset->sum(DB::raw('(nilai_perolehan - nilai_residu) / masa_manfaat_bulan'));

        $bebanPenyusutanHarian = ($calculatedDaysInMonth > 0) ? $totalPenyusutanBulananAktif / $calculatedDaysInMonth : 0;
        $bebanPenyusutanPeriodik = $bebanPenyusutanHarian * $calculatedJumlahHariPeriode;

        // D. Beban Tetap Operasional (Fixed Cost Prorated / Sinking Fund Allocation)
        $queryBebanBulan = BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'bulanan')
            ->where('created_at', '<=', $endRange);
        $queryBebanTahun = BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'tahunan')
            ->where('created_at', '<=', $endRange);
            
        if ($cabangId) {
            $queryBebanBulan->where('cabang_id', $cabangId);
            $queryBebanTahun->where('cabang_id', $cabangId);
        }

        $totalBebanBulananAktif = $queryBebanBulan->sum('nominal');
        $totalBebanTahunanAktif = $queryBebanTahun->sum('nominal');

        $bebanTetapPeriodik = 0;
        if ($totalBebanBulananAktif > 0 || $totalBebanTahunanAktif > 0) {
            $current = $startRange->copy();
            while ($current->lte($endRange)) {
                $harianBulanan = $totalBebanBulananAktif / $current->daysInMonth;
                $harianTahunan = $totalBebanTahunanAktif / $current->daysInYear;
                $bebanTetapPeriodik += ($harianBulanan + $harianTahunan);
                $current->addDay();
            }
        }

        $totalExpenses = $biayaOperasionalLokal + $biayaKomisi + $bebanPenyusutanPeriodik + $bebanTetapPeriodik;

        // NET PROFIT
        $netProfit = $grossProfit - $totalExpenses;

        $detailBeban = [
            'HPP (Modal Pokok Penjualan)' => $hpp,
            'Biaya Operasional Lokal' => $biayaOperasionalLokal,
            'Biaya Komisi Teknisi' => $biayaKomisi,
        ];

        if (!$cabangId) {
            $detailBeban['Beban Penyusutan Aset'] = $bebanPenyusutanPeriodik;
            $detailBeban['Beban Tetap Periodik'] = $bebanTetapPeriodik;
        }

        return [
            'revenue' => $revenue,
            'laba_kotor' => round($grossProfit, 0),
            'gross_profit' => round($grossProfit, 0),
            'total_beban' => round(array_sum($detailBeban), 0),
            'laba_bersih' => round($netProfit, 0),
            'net_profit' => round($netProfit, 0),
            'detail_beban' => array_map(function($value) { return round($value, 0); }, $detailBeban),
            'detail_hpp' => [], // raw HPP details are no longer queried for performance, return empty array to keep contract
            'jumlah_hari_periode' => $calculatedJumlahHariPeriode,
        ];
    }

    /**
     * Menghitung HPP (Cost of Goods Sold)
     * Total Modal dari barang/jasa yang laku terjual.
     * Updated: Now returns array with breakdown
     */
    public function calculateCOGS($ownerId, $startDate, $endDate)
    {
        $startRange = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endRange = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);

        $totalHPP = 0;
        
        $breakdown = [
            'part_toko_service' => 0,
            'part_luar_service' => 0,
            'barang_retail' => 0,
            'sparepart_retail' => 0
        ];

        // A. HPP dari Service (Part Toko & Part Luar)
        // Cari service yang selesai/diambil pada periode ini
        $services = Sevices::where('kode_owner', $ownerId)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange, $endRange])
            ->pluck('id');

        if ($services->isNotEmpty()) {
            // Part Toko
            // Fix: Join with spareparts to get harga_beli if detail_modal_part_service is 0 (Backward Compatibility)
            $partTokoHPP = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                ->whereIn('detail_part_services.kode_services', $services)
                ->sum(DB::raw('CASE 
                    WHEN detail_part_services.detail_modal_part_service > 0 THEN detail_part_services.detail_modal_part_service 
                    ELSE spareparts.harga_beli 
                END * detail_part_services.qty_part'));
            
            // Part Luar
            $partLuarHPP = DetailPartLuarService::whereIn('kode_services', $services)
                ->select(DB::raw('SUM(CASE WHEN harga_beli > 0 THEN harga_beli ELSE harga_part END * qty_part) as total_hpp'))
                ->value('total_hpp');
                
            $breakdown['part_toko_service'] = (float) $partTokoHPP;
            $breakdown['part_luar_service'] = (float) $partLuarHPP;

            $totalHPP += ($partTokoHPP + $partLuarHPP);
        }

        // B. HPP dari Penjualan Langsung
        $penjualans = Penjualan::where('kode_owner', $ownerId)
            ->where('status_penjualan', '1') // Selesai
            ->whereBetween('updated_at', [$startRange, $endRange]) // Consistent with calculateNetProfit
            ->pluck('id');

        if ($penjualans->isNotEmpty()) {
            // Barang Retail
            $barangHPP = DetailBarangPenjualan::whereIn('kode_penjualan', $penjualans)
                ->sum(DB::raw('detail_harga_modal * qty_barang'));

            // Sparepart Retail
            $sparepartHPP = DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualans)
                ->sum(DB::raw('detail_harga_modal * qty_sparepart'));

            $breakdown['barang_retail'] = (float) $barangHPP;
            $breakdown['sparepart_retail'] = (float) $sparepartHPP;

            $totalHPP += ($barangHPP + $sparepartHPP);
        }

        return [
            'total' => $totalHPP,
            'breakdown' => $breakdown
        ];
    }

    /**
     * Mencatat Transaksi ke Buku Besar (KasPerusahaan)
     */
    public function recordTransaction($ownerId, $date, $debit, $kredit, $description, $sourceType = null, $sourceId = null)
    {
        // Hitung saldo terakhir
        $lastBalance = KasPerusahaan::where('kode_owner', $ownerId)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->value('saldo') ?? 0;

        $newBalance = $lastBalance + $debit - $kredit;

        return KasPerusahaan::create([
            'kode_owner' => $ownerId,
            'tanggal' => $date,
            'debit' => $debit,
            'kredit' => $kredit,
            'saldo' => $newBalance,
            'deskripsi' => $description,
            'sourceable_type' => $sourceType,
            'sourceable_id' => $sourceId
        ]);
    }
}
