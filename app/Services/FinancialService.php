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
    public function calculateNetProfit($ownerId, $startDate, $endDate)
    {
        $startRange = $startDate instanceof Carbon ? $startDate : Carbon::parse($startDate);
        $endRange = $endDate instanceof Carbon ? $endDate : Carbon::parse($endDate);
        
        $startRange = $startRange->copy()->startOfDay();
        $endRange = $endRange->copy()->endOfDay();

        // Variabel kunci untuk prorata
        $calculatedJumlahHariPeriode = $startRange->diffInDays($endRange) + 1;
        $calculatedDaysInMonth = $startRange->daysInMonth;
        $calculatedDaysInYear = $startRange->daysInYear;

        // ==============================
        // 1. REVENUE (Pendapatan)
        // ==============================
        $totalPendapatanPenjualan = Penjualan::where('kode_owner', $ownerId)
            ->where('status_penjualan', '1')
            ->whereBetween('updated_at', [$startRange, $endRange])
            ->sum('total_penjualan');

        $totalPendapatanService = Sevices::where('kode_owner', $ownerId)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startRange, $endRange])
            ->sum('total_biaya');

        $totalPemasukkanLain = PemasukkanLain::where('kode_owner', $ownerId)
            ->whereBetween('created_at', [$startRange, $endRange])
            ->sum('jumlah_pemasukkan');

        $revenue = $totalPendapatanPenjualan + $totalPendapatanService + $totalPemasukkanLain;

        // ==============================
        // 2. COGS / HPP (Harga Pokok Penjualan)
        // ==============================
        // Menggunakan method calculateCOGS yang sudah dioptimalkan
        $hppResult = $this->calculateCOGS($ownerId, $startRange, $endRange);
        
        // Handle backward compatibility jika calculateCOGS mengembalikan float (old version)
        if (is_array($hppResult)) {
            $hpp = $hppResult['total'];
            $hppDetails = $hppResult['breakdown'];
        } else {
            $hpp = $hppResult;
            $hppDetails = [];
        }

        // ==============================
        // 3. LABA KOTOR
        // ==============================
        $grossProfit = $revenue - $hpp;

        // ==============================
        // 4. EXPENSES (Beban-beban)
        // ==============================
        
        // A. Biaya Operasional Insidental (Pengeluaran Toko - Legacy)
        $biayaOperasionalInsidental = PengeluaranToko::where('kode_owner', $ownerId)
            ->whereBetween('tanggal_pengeluaran', [$startRange, $endRange])
            ->sum('jumlah_pengeluaran');

        // A.2 Biaya Operasional (PengeluaranOperasional - New)
        // EXCLUDE Sinking Fund Payments (beban_operasional_id IS NOT NULL)
        // Karena beban sinking fund sudah dihitung harian di bagian "D. Beban Tetap Operasional"
        $biayaOperasionalBaru = PengeluaranOperasional::where('kode_owner', $ownerId)
            ->whereBetween('tgl_pengeluaran', [$startRange, $endRange])
            ->whereNull('beban_operasional_id') // Hanya ambil yang insidental / non-sinking fund
            ->sum('jml_pengeluaran');

        // B. Biaya Komisi (Dari Service)
        $serviceIdsSelesai = Sevices::where('kode_owner', $ownerId)
            ->whereIn('status_services', ['Selesai', 'Diambil'])
            ->whereBetween('tgl_service', [$startRange, $endRange])->pluck('id');
        $biayaKomisi = ProfitPresentase::whereIn('kode_service', $serviceIdsSelesai)->sum('profit');

        // C. Beban Penyusutan Aset (Depreciation)
        $totalPenyusutanBulananAktif = Aset::where('kode_owner', $ownerId)
            ->where('tanggal_perolehan', '<=', $endRange->toDateString())
            ->sum(DB::raw('(nilai_perolehan - nilai_residu) / masa_manfaat_bulan'));

        $bebanPenyusutanHarian = ($calculatedDaysInMonth > 0) ? $totalPenyusutanBulananAktif / $calculatedDaysInMonth : 0;
        $bebanPenyusutanPeriodik = $bebanPenyusutanHarian * $calculatedJumlahHariPeriode;

        // D. Beban Tetap Operasional (Fixed Cost Prorated / Sinking Fund Allocation)
        $totalBebanBulananAktif = BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'bulanan')
            ->where('created_at', '<=', $endRange)
            ->sum('nominal');

        $totalBebanTahunanAktif = BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'tahunan')
            ->where('created_at', '<=', $endRange)
            ->sum('nominal');

        // Hitung per hari (looping untuk akurasi lintas bulan/tahun)
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

        $totalExpenses = $biayaOperasionalInsidental + $biayaOperasionalBaru + $biayaKomisi + $bebanPenyusutanPeriodik + $bebanTetapPeriodik;

        // ==============================
        // 5. NET PROFIT
        // ==============================
        $netProfit = $grossProfit - $totalExpenses;

        $detailBeban = [
            'HPP (Modal Pokok Penjualan)' => $hpp,
            'Biaya Operasional (Legacy)' => $biayaOperasionalInsidental,
            'Biaya Operasional (New)' => $biayaOperasionalBaru,
            'Biaya Komisi Teknisi' => $biayaKomisi,
            'Beban Penyusutan Aset' => $bebanPenyusutanPeriodik,
            'Beban Tetap Periodik' => $bebanTetapPeriodik,
        ];

        return [
            'revenue' => $revenue, // Added for consistency with previous version
            'laba_kotor' => round($grossProfit, 0),
            'gross_profit' => round($grossProfit, 0), // Added alias
            'total_beban' => round(array_sum($detailBeban), 0),
            'laba_bersih' => round($netProfit, 0),
            'net_profit' => round($netProfit, 0), // Added alias
            'detail_beban' => array_map(function($value) { return round($value, 0); }, $detailBeban),
            'detail_hpp' => $hppDetails, // NEW: Return detail HPP
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
