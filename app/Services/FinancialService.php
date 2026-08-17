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

        // 1. REVENUE & HPP DARI SERVICE
        $servicesQuery = \App\Models\Sevices::where('kode_owner', $ownerId)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
        
        if ($cabangId) {
            $servicesQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        
        $services = $servicesQuery->get();
        $serviceIds = $services->pluck('id');
        $revenueService = $services->sum(function($s) { return $s->total_biaya - $s->dp; });

        // DP Service
        $dpQuery = \App\Models\Sevices::where('kode_owner', $ownerId)
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
        if ($cabangId) {
            $dpQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        $revenueDp = $dpQuery->sum('dp');

        // HPP Service
        $partTokoHPP = 0;
        $partLuarHPP = 0;
        if ($serviceIds->isNotEmpty()) {
            $partTokoHPP = \App\Models\DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                ->whereIn('detail_part_services.kode_services', $serviceIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('CASE 
                    WHEN detail_part_services.detail_modal_part_service > 0 THEN detail_part_services.detail_modal_part_service 
                    ELSE spareparts.harga_beli 
                END * detail_part_services.qty_part'));

            $partLuarHPP = \App\Models\DetailPartLuarService::whereIn('kode_services', $serviceIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('CASE WHEN harga_beli > 0 THEN harga_beli ELSE harga_part END * qty_part'));
        }

        // 2. REVENUE & HPP DARI PENJUALAN
        $penjualanQuery = \App\Models\Penjualan::where('kode_owner', $ownerId)
            ->where('status_penjualan', '1')
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
        
        if ($cabangId) {
            $penjualanQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        
        $penjualans = $penjualanQuery->get();
        $penjualanIds = $penjualans->pluck('id');
        $revenuePenjualan = $penjualans->sum('total_penjualan');

        // HPP Penjualan
        $barangHPP = 0;
        $sparepartHPP = 0;
        if ($penjualanIds->isNotEmpty()) {
            $barangHPP = \App\Models\DetailBarangPenjualan::whereIn('kode_penjualan', $penjualanIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('detail_harga_modal * qty_barang'));

            $sparepartHPP = \App\Models\DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualanIds)
                ->sum(\Illuminate\Support\Facades\DB::raw('detail_harga_modal * qty_sparepart'));
        }

        // 3. PEMASUKAN LAIN (termasuk modal pemasukan untuk HPP)
        $pemasukanQuery = \App\Models\PemasukkanLain::where('kode_owner', $ownerId)
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
        if ($cabangId) {
            $pemasukanQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        
        // Pendapatan Lain (hanya yang sifatnya laba/pendapatan, bukan titipan)
        $revenuePemasukanLain = (clone $pemasukanQuery)
            ->whereIn('sifat_pemasukan', ['laba', 'pendapatan'])
            ->sum('jumlah_pemasukkan');
            
        $totalModalPendapatan = (clone $pemasukanQuery)
            ->where('sifat_pemasukan', 'pendapatan')
            ->sum('modal_pemasukan');

        // SUM REVENUE & HPP
        $revenue = $revenueService + $revenueDp + $revenuePenjualan + $revenuePemasukanLain;
        $hpp = $partTokoHPP + $partLuarHPP + $barangHPP + $sparepartHPP + $totalModalPendapatan;
        
        // LABA KOTOR
        $grossProfit = $revenue - $hpp;

        // 4. BIAYA OPERASIONAL LOKAL & KOMISI
        $pengeluaranTokoQuery = \App\Models\PengeluaranToko::where('kode_owner', $ownerId)
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
        $pengeluaranOpsQuery = \App\Models\PengeluaranOperasional::where('kode_owner', $ownerId)
            ->whereNull('beban_operasional_id')
            ->whereBetween('created_at', [$startRange->toDateTimeString(), $endRange->toDateTimeString()]);
            
        if ($cabangId) {
            $pengeluaranTokoQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
            $pengeluaranOpsQuery->whereHas('shift', function ($q) use ($cabangId) {
                $q->where('cabang_id', $cabangId);
            });
        }
        
        $biayaOperasionalLokal = $pengeluaranTokoQuery->sum('jumlah_pengeluaran') + $pengeluaranOpsQuery->sum('jml_pengeluaran');

        $biayaKomisi = 0;
        if ($serviceIds->isNotEmpty()) {
            $biayaKomisi = \App\Models\ProfitPresentase::whereIn('kode_service', $serviceIds)->sum('profit');
        }

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
