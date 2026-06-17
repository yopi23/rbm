<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailBarangPesanan;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\DetailSparepartPesanan;
use App\Models\PemasukkanLain;
use App\Models\Penarikan;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\Pesanan;
use App\Models\Sevices;
use App\Models\HistoryLaci;
use App\Models\ProfitPresentase;
use App\Models\DistribusiSetting;
use App\Models\DistribusiLaba;
use App\Models\AlokasiLaba;
use App\Models\Aset;
use App\Models\BebanOperasional;
use App\Models\KasPerusahaan;
use App\Models\Sparepart;
use App\Models\Handphone;
use App\Scopes\ActiveScope;
use App\Models\Pembelian; // Added
use App\Models\Hutang; // Added
use App\Models\PengeluaranOperasional as PengeluaranOperasionalModel;
use App\Traits\KategoriLaciTrait;
use App\Traits\ManajemenKasTrait;
use App\Services\FinancialService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Carbon\Carbon;

class FinancialReportApiController extends Controller
{
    use KategoriLaciTrait;
    use ManajemenKasTrait;

    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    /**
     * Get comprehensive financial report
     * REFACTORED: Now uses FinancialService for consistent logic with Admin Panel
     */
    public function getFinancialReport(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'cabang_id' => 'nullable|integer',
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $cabangId = $request->input('cabang_id');
            $kode_owner = $this->getThisUser()->id_upline;

            $shiftIds = null;
            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            }

            // ==============================================
            // 1. PROFIT ANALYSIS (ACCRUAL BASIS) - Using FinancialService
            // ==============================================
            $profitData = $this->financialService->calculateNetProfit($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);

            // ==============================================
            // 2. CASH FLOW CALCULATION (CASH BASIS)
            // ==============================================
            
            // Cash In
            $totalCashInServiceQuery = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInServiceQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInService = $totalCashInServiceQuery->sum(DB::raw('total_biaya - dp'));
                
            $totalCashInDPQuery = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInDPQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInDP = $totalCashInDPQuery->sum('dp');

            $totalCashInSalesQuery = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInSalesQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInSales = $totalCashInSalesQuery->sum('total_penjualan');

            $totalCashInOtherQuery = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInOtherQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInOther = $totalCashInOtherQuery->sum('jumlah_pemasukkan');

            // Breakdown pemasukan lainnya cash dan transfer
            $totalCashInOtherCashQuery = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInOtherCashQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInOtherCash = $totalCashInOtherCashQuery->sum('jumlah_cash');

            $totalCashInOtherTransferQuery = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashInOtherTransferQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashInOtherTransfer = $totalCashInOtherTransferQuery->sum('jumlah_transfer');

            // Uang real di laci dan bank dari penjualan, pengambilan, dan DP service
            $uangRealDiLaciPenjualanQuery = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiLaciPenjualanQuery->whereIn('shift_id', $shiftIds);
            }
            $uangRealDiLaciPenjualan = $uangRealDiLaciPenjualanQuery->sum('jumlah_cash');

            $uangRealDiBankPenjualanQuery = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiBankPenjualanQuery->whereIn('shift_id', $shiftIds);
            }
            $uangRealDiBankPenjualan = $uangRealDiBankPenjualanQuery->sum('jumlah_transfer');

            $uangRealDiLaciPengambilanQuery = \App\Models\Pengambilan::where('kode_owner', $kode_owner)
                ->where('status_pengambilan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiLaciPengambilanQuery->whereIn('user_input', \App\Models\User::where('cabang_id', $cabangId)->pluck('id'));
            }
            $uangRealDiLaciPengambilan = $uangRealDiLaciPengambilanQuery->sum('jumlah_cash');

            $uangRealDiBankPengambilanQuery = \App\Models\Pengambilan::where('kode_owner', $kode_owner)
                ->where('status_pengambilan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiBankPengambilanQuery->whereIn('user_input', \App\Models\User::where('cabang_id', $cabangId)->pluck('id'));
            }
            $uangRealDiBankPengambilan = $uangRealDiBankPengambilanQuery->sum('jumlah_transfer');

            $uangRealDiLaciDPQuery = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiLaciDPQuery->whereIn('shift_id', $shiftIds);
            }
            $uangRealDiLaciDP = $uangRealDiLaciDPQuery->sum('dp_cash');

            $uangRealDiBankDPQuery = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $uangRealDiBankDPQuery->whereIn('shift_id', $shiftIds);
            }
            $uangRealDiBankDP = $uangRealDiBankDPQuery->sum('dp_transfer');

            $totalCashIn = $totalCashInService + $totalCashInDP + $totalCashInSales + $totalCashInOther;

            // Cash Out
            $totalCashOutStoreQuery = PengeluaranToko::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutStoreQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashOutStore = $totalCashOutStoreQuery->sum('jumlah_pengeluaran');

            $totalCashOutOperationalQuery = PengeluaranOperasional::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutOperationalQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashOutOperational = $totalCashOutOperationalQuery->sum('jml_pengeluaran');

            $totalCashOutWithdrawalQuery = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutWithdrawalQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashOutWithdrawal = $totalCashOutWithdrawalQuery->sum('jumlah_penarikan');

            // Breakdown penarikan cash dan transfer
            $totalCashOutWithdrawalCashQuery = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutWithdrawalCashQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashOutWithdrawalCash = $totalCashOutWithdrawalCashQuery->sum('jumlah_cash');

            $totalCashOutWithdrawalTransferQuery = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutWithdrawalTransferQuery->whereIn('shift_id', $shiftIds);
            }
            $totalCashOutWithdrawalTransfer = $totalCashOutWithdrawalTransferQuery->sum('jumlah_transfer');

            // Hitung uang real di laci dan bank setelah dikurangi penarikan
                $uangRealDiLaci = $uangRealDiLaciPenjualan + $uangRealDiLaciPengambilan + $uangRealDiLaciDP - $totalCashOutWithdrawalCash + $totalCashInOtherCash;
                $uangRealDiBank = $uangRealDiBankPenjualan + $uangRealDiBankPengambilan + $uangRealDiBankDP - $totalCashOutWithdrawalTransfer + $totalCashInOtherTransfer;
            
            // NEW: Pembelian Stok (Tunai)
            $totalPembelianTunaiQuery = Pembelian::where('kode_owner', $kode_owner)
                ->where('status', 'selesai')
                ->where('metode_pembayaran', '!=', 'Hutang')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalPembelianTunaiQuery->whereIn('shift_id', $shiftIds);
            }
            $totalPembelianTunai = $totalPembelianTunaiQuery->sum('total_harga');

            // NEW: Pembayaran Hutang
            $totalBayarHutangQuery = Hutang::where('kode_owner', $kode_owner)
                ->where('status', 'Lunas')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalBayarHutangQuery->whereHas('pembelian', function($q) use ($shiftIds) {
                    $q->whereIn('shift_id', $shiftIds);
                });
            }
            $totalBayarHutang = $totalBayarHutangQuery->sum('total_hutang');
            
            $totalCashOutPartsLuarQuery = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $totalCashOutPartsLuarQuery->whereIn('sevices.shift_id', $shiftIds);
            }
            $totalCashOutPartsLuar = $totalCashOutPartsLuarQuery->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));
            
            // Total Cash Out Updated
            $totalCashOut = $totalCashOutStore + 
                           $totalCashOutOperational + 
                           $totalCashOutWithdrawal + 
                           $totalCashOutPartsLuar;

            $netCashFlow = $totalCashIn - $totalCashOut;

             // 3. Service Stats
            $serviceStatsQuery = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $serviceStatsQuery->whereIn('shift_id', $shiftIds);
            }
            $serviceStats = $serviceStatsQuery->selectRaw('COUNT(*) as total, SUM(CASE WHEN total_biaya < 0 THEN 1 ELSE 0 END) as rugi_count')
                ->first();

            
            
            // Response data structure mapping
            $reportData = [
                // CASH FLOW
                'total_pendapatan' => $totalCashIn,
                'total_pengeluaran' => $totalCashOut,
                'saldo_kas' => $netCashFlow,
                'uang_real_di_laci' => $uangRealDiLaci,
                'uang_real_di_bank' => $uangRealDiBank,
                'part_toko_belum_dibayar' => 0,

                // CASH FLOW DETAIL
                'total_pendapatan_service' => $totalCashInService,
                'dp_service' => $totalCashInDP,
                'total_penjualan' => $totalCashInSales,
                'total_pemasukkan_lain' => $totalCashInOther,
                'total_pemasukkan_lain_cash' => $totalCashInOtherCash,
                'total_pemasukkan_lain_transfer' => $totalCashInOtherTransfer,
                
                // Expenses Breakdown
                'total_pengeluaran_toko' => $totalCashOutStore,
                'total_pengeluaran_operasional' => $totalCashOutOperational,
                'total_penarikan' => $totalCashOutWithdrawal,
                'total_penarikan_cash' => $totalCashOutWithdrawalCash,
                'total_penarikan_transfer' => $totalCashOutWithdrawalTransfer,
                'total_pembelian_tunai' => $totalPembelianTunai, // NEW
                'total_bayar_hutang' => $totalBayarHutang, // NEW
                'total_part_luar' => $totalCashOutPartsLuar, // NEW (Replacing total_part_service mix)
                'total_part_service' => $totalCashOutPartsLuar, // Legacy field for compatibility 

                // PROFIT ANALYSIS (FROM TRAIT - ACCURATE)
                'laba_service' => 0, // Combined in laba_kotor now
                'laba_penjualan' => 0, // Combined in laba_kotor now
                'total_laba_kotor' => $profitData['laba_kotor'],
                'total_beban_operasional' => $profitData['total_beban'],
                'laba_bersih_bisnis' => $profitData['laba_bersih'],
                'detail_beban' => $profitData['detail_beban'],
                'detail_hpp' => $profitData['detail_hpp'] ?? [], // NEW: Return detail HPP

                // ANALISIS TAMBAHAN
                'service_rugi_count' => $serviceStats->rugi_count ?? 0,
                'service_profit_count' => ($serviceStats->total ?? 0) - ($serviceStats->rugi_count ?? 0),
                'analisis_part_toko' => $this->getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId), // Restored
                
                // BACKWARD COMPATIBILITY
                'laba_bersih' => $profitData['laba_bersih'], 

                // META
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir,
                    'jumlah_hari' => $profitData['jumlah_hari_periode']
                ]
            ];

            return response()->json([
                'success' => true,
                'message' => 'Laporan keuangan berhasil diambil',
                'data' => $reportData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Financial Report Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get financial summary for dashboard
     * REFACTORED: Uses the same logic as Admin Dashboard (POSY style)
     */
    public function getFinancialSummary(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $today = Carbon::today()->format('Y-m-d');
            $cabangId = $request->input('cabang_id');
            
            $stats = $this->_getDashboardStats($kode_owner, $today, $cabangId);

            // Add extra info for mobile dashboard
            $recentQuery = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil');
            
            $pendingQuery = Sevices::where('kode_owner', $kode_owner)
                ->whereIn('status_services', ['Antri', 'Proses']);

            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
                $recentQuery->whereIn('shift_id', $shiftIds);
                $pendingQuery->whereIn('shift_id', $shiftIds);
            }

            $stats['recent_completed_services'] = $recentQuery->orderBy('updated_at', 'desc')
                ->take(5)
                ->select('kode_service', 'nama_pelanggan', 'total_biaya', 'updated_at')
                ->get();

            $stats['pending_services_count'] = $pendingQuery->count();

            return response()->json([
                'success' => true,
                'message' => 'Ringkasan keuangan berhasil diambil',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Private helper to calculate dashboard stats (Revenue, Assets, Capital, Profit)
     * Matches Admin\FinancialController logic exactly.
     */
    private function _getDashboardStats(int $ownerId, string $date, int $cabangId = null): array
    {
        // 1. Profit Summary (Operational)
        $labaResult = $this->financialService->calculateNetProfit($ownerId, $date, $date, $cabangId);
        
        $totalRevenue = $labaResult['laba_kotor'] + ($labaResult['detail_beban']['HPP (Modal Pokok Penjualan)'] ?? 0);
        
        $operatingExpenses = ($labaResult['detail_beban']['Biaya Operasional Lokal'] ?? 0) 
                             + ($labaResult['detail_beban']['Biaya Komisi Teknisi'] ?? 0);
                             
        $depreciation = $labaResult['detail_beban']['Beban Penyusutan Aset'] ?? 0;
        $bebanTetap = $labaResult['detail_beban']['Beban Tetap Periodik'] ?? 0;
        $netProfit = $labaResult['laba_bersih'];

        // 2. Inventory Value (ASSET) - Hanya dihitung di level Owner/Holding (jika cabangId null)
        $inventoryValue = 0;
        if (!$cabangId) {
            $nilaiSparepart = Sparepart::withoutGlobalScope(ActiveScope::class)->where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
            $nilaiHandphone = Handphone::withoutGlobalScope(ActiveScope::class)->where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
            $inventoryValue = $nilaiSparepart + $nilaiHandphone;
        }

        // 3. Saldo Kas (ASSET) - Source of Truth: KasPerusahaan Ledger
        $endDate = Carbon::parse($date)->endOfDay();
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            $saldoKas = KasPerusahaan::whereIn('shift_id', $shiftIds)
                ->where('tanggal', '<=', $endDate)
                ->sum(DB::raw('debit - kredit'));
        } else {
            $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)
                ->where('tanggal', '<=', $endDate)
                ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')
                ->first()->saldo ?? 0;
        }

        // 4. Modal Disetor (Paid In Capital) - Hanya dihitung di level Owner/Holding
        $paidInCapital = 0;
        if (!$cabangId) {
            $capitalIn = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
                ->whereIn('jenis_transaksi', ['setoran_awal', 'tambahan_modal'])
                ->where('tanggal', '<=', $endDate)
                ->sum('jumlah');

            $capitalOut = TransaksiModal::where('kode_owner', $ownerId)
                ->where('jenis_transaksi', 'penarikan_modal')
                ->where('tanggal', '<=', $endDate)
                ->sum('jumlah');
                
            $paidInCapital = $capitalIn - $capitalOut;
        }

        // 5. Total Asset
        $queryAsetTotal = Aset::where('kode_owner', $ownerId)
             ->where('tanggal_perolehan', '<=', $endDate);
        if ($cabangId) {
            $queryAsetTotal->where('cabang_id', $cabangId);
        }
        $asetTetap = $queryAsetTotal->sum('nilai_perolehan');
             
        $totalAsset = $saldoKas + $inventoryValue + $asetTetap;

        return [
            'revenue' => $totalRevenue,
            'expense' => $operatingExpenses + $depreciation + $bebanTetap,
            'net_profit' => $netProfit,
            'total_asset' => $totalAsset,
            'paid_in_capital' => $paidInCapital,
            'cash_balance' => $saldoKas,
            'inventory_value' => $inventoryValue,
            'fixed_assets' => $asetTetap,
            'generated_at' => Carbon::now()->toISOString()
        ];
    }

    /**
     * NEW: Get service loss details
     */
    public function getServiceLossDetails(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'cabang_id' => 'nullable|integer'
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $cabangId = $request->input('cabang_id');
            $kode_owner = $this->getThisUser()->id_upline;

            Log::info('Service Loss Details Request', [
                'user_id' => auth()->user()->id,
                'period' => $tgl_awal . ' to ' . $tgl_akhir,
                'cabang_id' => $cabangId
            ]);

            // Ambil semua service dalam periode
            $servicesQuery = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
                $servicesQuery->whereIn('shift_id', $shiftIds);
            }
            $services = $servicesQuery->get();

            $lossServices = [];
            $totalLoss = 0;
            $worstLoss = 0;

            foreach ($services as $service) {
                // Hitung total biaya parts
                $totalPartsCost = 0;
                $partsToko = [];
                $partsLuar = [];

                // Parts dari toko
                $partsTokoData = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->where('detail_part_services.kode_services', $service->id)
                    ->select(
                        'spareparts.nama_sparepart',
                        'detail_part_services.qty_part',
                        'detail_part_services.detail_harga_part_service'
                    )
                    ->get();

                foreach ($partsTokoData as $part) {
                    $partCost = $part->detail_harga_part_service * $part->qty_part;
                    $totalPartsCost += $partCost;

                    $partsToko[] = [
                        'nama_sparepart' => $part->nama_sparepart,
                        'qty_part' => $part->qty_part,
                        'detail_harga_part_service' => $part->detail_harga_part_service,
                        'total_cost' => $partCost
                    ];
                }

                // Parts dari luar
                $partsLuarData = DetailPartLuarService::where('kode_services', $service->id)->get();
                foreach ($partsLuarData as $part) {
                    $partCost = $part->harga_part * $part->qty_part;
                    $totalPartsCost += $partCost;

                    $partsLuar[] = [
                        'nama_part' => $part->nama_part,
                        'qty_part' => $part->qty_part,
                        'harga_part' => $part->harga_part,
                        'total_cost' => $partCost
                    ];
                }

                // Hitung profit/loss
                $serviceProfit = $service->total_biaya - $totalPartsCost;

                // Jika service rugi, masukkan ke data
                if ($serviceProfit < 0) {
                    $serviceLoss = abs($serviceProfit);
                    $totalLoss += $serviceLoss;

                    if ($serviceLoss > $worstLoss) {
                        $worstLoss = $serviceLoss;
                    }

                    $lossServices[] = [
                        'kode_service' => $service->kode_service,
                        'nama_pelanggan' => $service->nama_pelanggan,
                        'type_unit' => $service->type_unit,
                        'total_biaya' => $service->total_biaya,
                        'total_parts_cost' => $totalPartsCost,
                        'service_loss' => $serviceLoss,
                        'loss_percentage' => $service->total_biaya > 0 ? ($serviceLoss / $service->total_biaya) * 100 : 0,
                        'parts_toko' => $partsToko,
                        'parts_luar' => $partsLuar,
                        'updated_at' => $service->updated_at,
                        'created_at' => $service->created_at
                    ];
                }
            }

            // Urutkan berdasarkan kerugian terbesar
            usort($lossServices, function($a, $b) {
                return $b['service_loss'] <=> $a['service_loss'];
            });

            // Summary
            $summary = [
                'total_services' => count($lossServices),
                'total_loss' => $totalLoss,
                'average_loss' => count($lossServices) > 0 ? $totalLoss / count($lossServices) : 0,
                'worst_loss' => $worstLoss,
                'loss_percentage_of_total' => $services->count() > 0 ? (count($lossServices) / $services->count()) * 100 : 0
            ];

            return response()->json([
                'success' => true,
                'message' => 'Detail service rugi berhasil diambil',
                'data' => $lossServices,
                'summary' => $summary,
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir,
                    'total_services_checked' => $services->count()
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Service Loss Details Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get detailed report by type
     */
    public function getDetailedReport(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'type' => 'required|in:service,penjualan,pemasukkan_lain,pengeluaran,penarikan,part_service,pengeluaran_toko,pengeluaran_operasional,pembelian_tunai,pembayaran_hutang',
                'cabang_id' => 'nullable|integer'
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $type = $request->type;
            $cabangId = $request->input('cabang_id');
            $kode_owner = $this->getThisUser()->id_upline;

            $shiftIds = null;
            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            }

            Log::info('Detailed Report Request', [
                'user_id' => auth()->user()->id,
                'type' => $type,
                'period' => $tgl_awal . ' to ' . $tgl_akhir,
                'cabang_id' => $cabangId
            ]);

            $data = [];

            switch ($type) {
                case 'service':
                    $data = $this->getServiceDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);
                    break;

                case 'penjualan':
                    $data = $this->getSalesDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);
                    break;

                case 'pemasukkan_lain':
                    $data = $this->getOtherIncomeDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);
                    break;

                case 'part_service':
                    $data = $this->getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);
                    break;

                case 'pengeluaran_toko':
                    $query = PengeluaranToko::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                    if ($cabangId) {
                        $query->whereIn('shift_id', $shiftIds);
                    }
                    $data = $query->select('nama_pengeluaran as judul', 'catatan_pengeluaran as catatan', 'jumlah_pengeluaran as jumlah', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Toko';
                            return $item;
                        });
                    break;

                case 'pengeluaran_operasional':
                    $query = PengeluaranOperasional::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                    if ($cabangId) {
                        $query->whereIn('shift_id', $shiftIds);
                    }
                    $data = $query->select('nama_pengeluaran as judul', 'desc_pengeluaran as catatan', 'jml_pengeluaran as jumlah', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Operasional';
                            return $item;
                        });
                    break;

                case 'pembelian_tunai':
                     $query = Pembelian::where('kode_owner', $kode_owner)
                        ->where('status', 'selesai')
                        ->where('metode_pembayaran', '!=', 'Hutang')
                        ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                     if ($cabangId) {
                         $query->whereIn('shift_id', $shiftIds);
                     }
                     $data = $query->select('kode_pembelian as judul', 'supplier as catatan', 'total_harga as jumlah', 'updated_at as created_at')
                        ->orderBy('updated_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pembelian Tunai';
                            return $item;
                        });
                    break;
                
                case 'pembayaran_hutang':
                     $query = Hutang::where('kode_owner', $kode_owner)
                        ->where('status', 'Lunas')
                        ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                     if ($cabangId) {
                         $query->whereHas('pembelian', function($q) use ($shiftIds) {
                             $q->whereIn('shift_id', $shiftIds);
                         });
                     }
                     $data = $query->with('supplier')
                        ->orderBy('updated_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            return [
                                'judul' => 'Bayar Hutang #' . $item->kode_nota,
                                'catatan' => 'Supplier: ' . ($item->supplier->nama_supplier ?? '-'),
                                'jumlah' => $item->total_hutang,
                                'created_at' => $item->updated_at,
                                'type' => 'Pembayaran Hutang'
                            ];
                        });
                    break;

                case 'pengeluaran':
                    $queryToko = PengeluaranToko::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                    if ($cabangId) {
                        $queryToko->whereIn('shift_id', $shiftIds);
                    }
                    $pengeluaranToko = $queryToko->select('nama_pengeluaran as judul', 'catatan_pengeluaran as catatan', 'jumlah_pengeluaran as jumlah', 'created_at')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Toko';
                            return $item;
                        });

                    $queryOps = PengeluaranOperasional::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
                    if ($cabangId) {
                        $queryOps->whereIn('shift_id', $shiftIds);
                    }
                    $pengeluaranOps = $queryOps->select('nama_pengeluaran as judul', 'desc_pengeluaran as catatan', 'jml_pengeluaran as jumlah', 'created_at')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Operasional';
                            return $item;
                        });

                    $data = $pengeluaranToko->concat($pengeluaranOps)->sortByDesc('created_at')->values();
                    break;

                case 'penarikan':
                    $data = $this->getWithdrawalDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId);
                    break;

                default:
                    throw new \Exception('Invalid report type: ' . $type);
            }

            return response()->json([
                'success' => true,
                'message' => 'Detail laporan berhasil diambil',
                'data' => $data,
                'type' => $type,
                'total_records' => count($data),
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Detailed Report Error', [
                'user_id' => auth()->user()->id ?? null,
                'type' => $request->type ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Helper Methods untuk Detail Reports
    private function getServiceDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId = null)
    {
        $query = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            $query->whereIn('shift_id', $shiftIds);
        }
        return $query->select('id', 'kode_service', 'nama_pelanggan', 'type_unit', 'total_biaya', 'dp', 'updated_at')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->map(function($item) {
                $item->net_amount = $item->total_biaya - $item->dp;

                // Hitung total biaya parts untuk analisis profit
                $totalPartsPrice = 0;

                $partsToko = DetailPartServices::where('kode_services', $item->id)->get();
                foreach ($partsToko as $part) {
                    $totalPartsPrice += $part->detail_harga_part_service;
                }

                $partsLuar = DetailPartLuarService::where('kode_services', $item->id)->get();
                foreach ($partsLuar as $part) {
                    $totalPartsPrice += ($part->harga_part * $part->qty_part);
                }

                $item->total_parts_cost = $totalPartsPrice;
                $item->service_profit = $item->total_biaya - $totalPartsPrice;
                $item->is_profitable = $item->service_profit >= 0;

                return $item;
            });
    }

    // BARU: Detail Part Service
    private function getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId = null)
    {
        $shiftIds = null;
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
        }

        $partTokoQuery = DB::table('detail_part_services')
            ->join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
            ->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $partTokoQuery->whereIn('sevices.shift_id', $shiftIds);
        }
        $partTokoDetails = $partTokoQuery->select(
                'sevices.kode_service',
                'sevices.nama_pelanggan',
                'spareparts.nama_sparepart',
                'detail_part_services.detail_harga_part_service as harga_jual',
                DB::raw('CASE WHEN detail_part_services.detail_modal_part_service > 0 THEN detail_part_services.detail_modal_part_service ELSE spareparts.harga_beli END as harga_modal'),
                'detail_part_services.qty_part as qty',
                'sevices.updated_at',
                DB::raw("'Part Toko' as source_type")
            )
            ->get()
            ->map(function($item) {
                $totalJual = $item->harga_jual * $item->qty;
                $totalModal = $item->harga_modal * $item->qty;
                $margin = $totalJual - $totalModal;

                $item->total_harga_jual = $totalJual;
                $item->total_harga_modal = $totalModal;
                $item->margin_keuntungan = $margin;
                $item->margin_persen = $totalJual > 0 ? ($margin / $totalJual) * 100 : 0;

                return $item;
            });

        $partLuarQuery = DB::table('detail_part_luar_services')
            ->join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $partLuarQuery->whereIn('sevices.shift_id', $shiftIds);
        }
        $partLuarDetails = $partLuarQuery->select(
                'sevices.kode_service',
                'sevices.nama_pelanggan',
                'detail_part_luar_services.nama_part as nama_sparepart',
                'detail_part_luar_services.harga_part as harga_jual',
                'detail_part_luar_services.harga_part as harga_modal',
                'detail_part_luar_services.qty_part as qty',
                'sevices.updated_at',
                DB::raw("'Part Luar' as source_type")
            )
            ->get()
            ->map(function($item) {
                $totalJual = $item->harga_jual * $item->qty;
                $totalModal = $item->harga_modal * $item->qty;
                $margin = 0;

                $item->total_harga_jual = $totalJual;
                $item->total_harga_modal = $totalModal;
                $item->margin_keuntungan = $margin;
                $item->margin_persen = 0;

                return $item;
            });

        return $partTokoDetails->concat($partLuarDetails)
            ->sortByDesc('updated_at')
            ->values();
    }

    private function getSalesDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId = null)
    {
        $query = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            $query->whereIn('shift_id', $shiftIds);
        }
        return $query->select('kode_penjualan', 'nama_customer', 'total_penjualan', 'total_bayar', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getOtherIncomeDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId = null)
    {
        $query = PemasukkanLain::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            $query->whereIn('shift_id', $shiftIds);
        }
        return $query->select('judul_pemasukan', 'catatan_pemasukkan', 'jumlah_pemasukkan', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getWithdrawalDetails($kode_owner, $tgl_awal, $tgl_akhir, $cabangId = null)
    {
        $query = Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')
            ->where('penarikans.kode_owner', $kode_owner)
            ->where('penarikans.status_penarikan', '1')
            ->whereBetween('penarikans.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);
        if ($cabangId) {
            $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            $query->whereIn('penarikans.shift_id', $shiftIds);
        }
        return $query->select(
                'penarikans.kode_penarikan',
                'user_details.fullname',
                'penarikans.jumlah_penarikan',
                'penarikans.catatan_penarikan',
                'penarikans.created_at'
            )
            ->orderBy('penarikans.created_at', 'desc')
            ->get();
    }

    /**
     * Get daily report breakdown
     */
    public function getDailyReport(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'cabang_id' => 'nullable|integer'
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $cabangId = $request->input('cabang_id');
            $kode_owner = $this->getThisUser()->id_upline;

            // Validasi rentang tanggal tidak lebih dari 90 hari
            $daysDiff = Carbon::parse($tgl_awal)->diffInDays(Carbon::parse($tgl_akhir));
            if ($daysDiff > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rentang tanggal tidak boleh lebih dari 90 hari'
                ], 422);
            }

            $shiftIds = null;
            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
            }

            Log::info('Daily Report Request', [
                'user_id' => auth()->user()->id,
                'period' => $tgl_awal . ' to ' . $tgl_akhir,
                'days_count' => $daysDiff + 1,
                'cabang_id' => $cabangId
            ]);

            $dailyData = [];
            $currentDate = Carbon::parse($tgl_awal);
            $endDate = Carbon::parse($tgl_akhir);

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');

                // Pendapatan harian
                $serviceIncomeQuery = Sevices::where('kode_owner', $kode_owner)
                    ->where('status_services', 'Diambil')
                    ->whereDate('updated_at', $dateStr);
                if ($cabangId) {
                    $serviceIncomeQuery->whereIn('shift_id', $shiftIds);
                }
                $serviceIncome = $serviceIncomeQuery->sum(DB::raw('total_biaya - dp'));

                $dpIncomeQuery = Sevices::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $dpIncomeQuery->whereIn('shift_id', $shiftIds);
                }
                $dpIncome = $dpIncomeQuery->sum('dp');

                $salesIncomeQuery = Penjualan::where('kode_owner', $kode_owner)
                    ->where('status_penjualan', '1')
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $salesIncomeQuery->whereIn('shift_id', $shiftIds);
                }
                $salesIncome = $salesIncomeQuery->sum('total_penjualan');

                $otherIncomeQuery = PemasukkanLain::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $otherIncomeQuery->whereIn('shift_id', $shiftIds);
                }
                $otherIncome = $otherIncomeQuery->sum('jumlah_pemasukkan');

                // Pengeluaran harian
                $partLuarExpenseQuery = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                    ->where('sevices.kode_owner', $kode_owner)
                    ->where('sevices.status_services', 'Diambil')
                    ->whereDate('sevices.updated_at', $dateStr);
                if ($cabangId) {
                    $partLuarExpenseQuery->whereIn('sevices.shift_id', $shiftIds);
                }
                $partLuarExpense = $partLuarExpenseQuery->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));
                
                $storeExpenseQuery = PengeluaranToko::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $storeExpenseQuery->whereIn('shift_id', $shiftIds);
                }
                $storeExpense = $storeExpenseQuery->sum('jumlah_pengeluaran');

                $operationalExpenseQuery = PengeluaranOperasional::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->whereNull('beban_operasional_id');
                if ($cabangId) {
                    $operationalExpenseQuery->whereIn('shift_id', $shiftIds);
                }
                $operationalExpense = $operationalExpenseQuery->sum('jml_pengeluaran');

                $withdrawalExpenseQuery = Penarikan::where('kode_owner', $kode_owner)
                    ->where('status_penarikan', '1')
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $withdrawalExpenseQuery->whereIn('shift_id', $shiftIds);
                }
                $withdrawalExpense = $withdrawalExpenseQuery->sum('jumlah_penarikan');
                
                $purchaseExpenseQuery = Pembelian::where('kode_owner', $kode_owner)
                    ->where('status', 'selesai')
                    ->where('metode_pembayaran', '!=', 'Hutang')
                    ->whereDate('updated_at', $dateStr);
                if ($cabangId) {
                    $purchaseExpenseQuery->whereIn('shift_id', $shiftIds);
                }
                $purchaseExpense = $purchaseExpenseQuery->sum('total_harga');

                $debtPaymentExpenseQuery = Hutang::where('kode_owner', $kode_owner)
                    ->where('status', 'Lunas')
                    ->whereDate('updated_at', $dateStr);
                if ($cabangId) {
                    $debtPaymentExpenseQuery->whereHas('pembelian', function($q) use ($shiftIds) {
                        $q->whereIn('shift_id', $shiftIds);
                    });
                }
                $debtPaymentExpense = $debtPaymentExpenseQuery->sum('total_hutang');

                $totalIncome = $serviceIncome + $dpIncome + $salesIncome + $otherIncome;
                $totalExpense = $partLuarExpense + $storeExpense + $operationalExpense + $withdrawalExpense + $purchaseExpense + $debtPaymentExpense;
                $netProfit = $totalIncome - $totalExpense;

                // Hitung jumlah transaksi
                $serviceCountQuery = Sevices::where('kode_owner', $kode_owner)
                    ->where('status_services', 'Diambil')
                    ->whereDate('updated_at', $dateStr);
                if ($cabangId) {
                    $serviceCountQuery->whereIn('shift_id', $shiftIds);
                }
                $serviceCount = $serviceCountQuery->count();

                $salesCountQuery = Penjualan::where('kode_owner', $kode_owner)
                    ->where('status_penjualan', '1')
                    ->whereDate('created_at', $dateStr);
                if ($cabangId) {
                    $salesCountQuery->whereIn('shift_id', $shiftIds);
                }
                $salesCount = $salesCountQuery->count();

                $dailyData[] = [
                    'date' => $dateStr,
                    'date_formatted' => $currentDate->format('d M Y'),
                    'day_name' => $currentDate->locale('id')->dayName,
                    'income' => [
                        'service' => (float) $serviceIncome,
                        'dp' => (float) $dpIncome,
                        'sales' => (float) $salesIncome,
                        'other' => (float) $otherIncome,
                        'total' => (float) $totalIncome
                    ],
                    'expense' => [
                        'parts_luar' => (float) $partLuarExpense,
                        'store' => (float) $storeExpense,
                        'operational' => (float) $operationalExpense,
                        'withdrawal' => (float) $withdrawalExpense,
                        'purchase' => (float) $purchaseExpense,
                        'debt_payment' => (float) $debtPaymentExpense,
                        'total' => (float) $totalExpense
                    ],
                    'net_profit' => (float) $netProfit,
                    'transaction_count' => [
                        'service' => $serviceCount,
                        'sales' => $salesCount
                    ]
                ];

                $currentDate->addDay();
            }

            // Hitung summary
            $totalIncomePeriod = array_sum(array_column(array_column($dailyData, 'income'), 'total'));
            $totalExpensePeriod = array_sum(array_column(array_column($dailyData, 'expense'), 'total'));
            $totalProfitPeriod = array_sum(array_column($dailyData, 'net_profit'));

            return response()->json([
                'success' => true,
                'message' => 'Laporan harian berhasil diambil',
                'data' => $dailyData,
                'summary' => [
                    'total_income' => $totalIncomePeriod,
                    'total_expense' => $totalExpensePeriod,
                    'total_profit' => $totalProfitPeriod,
                    'average_daily_income' => count($dailyData) > 0 ? $totalIncomePeriod / count($dailyData) : 0,
                    'average_daily_expense' => count($dailyData) > 0 ? $totalExpensePeriod / count($dailyData) : 0,
                    'average_daily_profit' => count($dailyData) > 0 ? $totalProfitPeriod / count($dailyData) : 0,
                ],
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir,
                    'jumlah_hari' => count($dailyData)
                ]
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            Log::error('Daily Report Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get profit analysis by technician
     */
    public function getTechnicianProfitAnalysis(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'cabang_id' => 'nullable|integer'
            ]);

            $kode_owner = $this->getThisUser()->id_upline;
            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $cabangId = $request->input('cabang_id');

            $query = DB::table('profit_presentases')
                ->join('sevices', 'profit_presentases.kode_service', '=', 'sevices.id')
                ->join('user_details', 'profit_presentases.kode_user', '=', 'user_details.kode_user')
                ->where('sevices.kode_owner', $kode_owner)
                ->whereBetween('profit_presentases.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59']);

            if ($cabangId) {
                $shiftIds = \App\Models\Shift::where('cabang_id', $cabangId)->pluck('id');
                $query->whereIn('sevices.shift_id', $shiftIds);
            }

            $technicianProfits = $query->select(
                    'user_details.fullname',
                    'user_details.kode_user',
                    DB::raw('COUNT(profit_presentases.id) as total_services'),
                    DB::raw('SUM(profit_presentases.profit) as total_profit'),
                    DB::raw('AVG(profit_presentases.profit) as avg_profit_per_service')
                )
                ->groupBy('user_details.kode_user', 'user_details.fullname')
                ->orderBy('total_profit', 'desc')
                ->get();

            // Hitung total keseluruhan
            $totalProfit = $technicianProfits->sum('total_profit');
            $totalServices = $technicianProfits->sum('total_services');

            // Tambahkan persentase untuk setiap teknisi
            $technicianProfits = $technicianProfits->map(function($item) use ($totalProfit) {
                $item->profit_percentage = $totalProfit > 0 ? ($item->total_profit / $totalProfit) * 100 : 0;
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Analisis profit teknisi berhasil diambil',
                'data' => [
                    'technicians' => $technicianProfits,
                    'summary' => [
                        'total_profit' => (float) $totalProfit,
                        'total_services' => (int) $totalServices,
                        'average_profit_per_service' => $totalServices > 0 ? $totalProfit / $totalServices : 0,
                        'active_technicians' => $technicianProfits->count()
                    ]
                ],
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Technician Profit Analysis Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export financial report to Excel/CSV
    /**
     * Export financial report to Excel/CSV
     */
    public function exportFinancialReport(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'format' => 'required|in:excel,csv',
                'type' => 'required|in:summary,detailed,daily'
            ]);

            // Untuk implementasi export, Anda bisa menggunakan package seperti:
            // - Laravel Excel (maatwebsite/excel)
            // - Atau generate CSV manual

            // Contoh response untuk saat ini
            return response()->json([
                'success' => true,
                'message' => 'Fitur export akan segera tersedia',
                'data' => [
                    'export_url' => null,
                    'format' => $request->format,
                    'type' => $request->type
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Monitor Nilai Stok vs Nilai Hutang
     * Membandingkan Total Nilai Stok Tersedia (Aset) dengan Total Hutang Supplier.
     */
    public function getStockDebtMonitor(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            // 1. Nilai Stok Sparepart (Aset)
            $nilaiSparepart = Sparepart::withoutGlobalScope(ActiveScope::class)
                ->where('kode_owner', $kode_owner)
                ->sum(DB::raw('stok_sparepart * harga_beli'));

            // 2. Nilai Stok Handphone (Aset)
            $nilaiHandphone = Handphone::withoutGlobalScope(ActiveScope::class)
                ->where('kode_owner', $kode_owner)
                ->sum(DB::raw('stok_barang * harga_beli_barang'));

            $totalNilaiStok = $nilaiSparepart + $nilaiHandphone;

            // 3. Saldo Kas Perusahaan (Buku Besar)
            $saldoKas = KasPerusahaan::where('kode_owner', $kode_owner)
                ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')
                ->first()->saldo ?? 0;

            // 4. Total Hutang Supplier (Belum Lunas)
            $totalHutang = Hutang::where('kode_owner', $kode_owner)
                ->where('status', 'Belum Lunas')
                ->sum('total_hutang');

            // 5. Hitung Likuiditas dan Selisih
            $selisihLikuiditas = $saldoKas - $totalHutang; // Net Cash Position after paying all debt
            $totalAsetLikuid = $saldoKas + $totalNilaiStok;
            $selisihAsetVsHutang = $totalAsetLikuid - $totalHutang;

            // Analisis Rincian & Status
            $statusKeamanan = 'Aman';
            $tingkatResiko = 'Rendah';
            $pesanAnalisis = '';

            if ($totalHutang == 0) {
                $statusKeamanan = 'Sangat Aman';
                $tingkatResiko = 'Rendah';
                $pesanAnalisis = 'Luar biasa! Bisnis Anda saat ini tidak memiliki hutang supplier yang belum lunas. Pertahankan disiplin keuangan ini.';
            } elseif ($totalAsetLikuid < $totalHutang) {
                $statusKeamanan = 'Bahaya (Defisit Total)';
                $tingkatResiko = 'Sangat Tinggi / Kritis';
                $pesanAnalisis = '⚠️ PERINGATAN KRITIS: Total hutang supplier Anda (Rp ' . number_format($totalHutang, 0, ',', '.') . ') sudah melebihi gabungan seluruh nilai stok barang dan saldo kas Anda (Rp ' . number_format($totalAsetLikuid, 0, ',', '.') . '). Ini mengindikasikan adanya KEBOCORAN UANG CASH yang sangat besar. Penyebab utama bisa berupa pengeluaran operasional insidental yang terlalu boros atau penarikan (pembagian dividen) owner yang melebihi keuntungan riil bisnis.';
            } elseif ($saldoKas < $totalHutang) {
                $statusKeamanan = 'Risiko Likuiditas (Kurang Kas)';
                $tingkatResiko = 'Sedang-Tinggi';
                $pesanAnalisis = '⚠️ PERINGATAN: Saldo kas Anda saat ini tidak cukup untuk melunasi hutang supplier secara langsung, meskipun total aset (kas + nilai stok barang) masih mencukupi. Bisnis Anda mengalami kemacetan arus kas karena uang tunai Anda terikat di dalam stok barang (stok mengendap) atau telah keluar untuk keperluan lain sebelum hutang supplier dibayar.';
            } else {
                $statusKeamanan = 'Aman';
                $tingkatResiko = 'Rendah';
                $pesanAnalisis = 'Arus kas bisnis Anda sehat. Saldo kas Anda saat ini cukup untuk menutupi seluruh kewajiban hutang supplier yang berjalan.';
            }

            return response()->json([
                'success' => true,
                'message' => 'Monitor Nilai Stok vs Nilai Hutang berhasil diambil',
                'data' => [
                    'nilai_stok_sparepart' => (float)$nilaiSparepart,
                    'nilai_stok_handphone' => (float)$nilaiHandphone,
                    'total_nilai_stok' => (float)$totalNilaiStok,
                    'saldo_kas' => (float)$saldoKas,
                    'total_hutang' => (float)$totalHutang,
                    'selisih_likuiditas' => (float)$selisihLikuiditas,
                    'total_aset_likuid' => (float)$totalAsetLikuid,
                    'selisih_aset_vs_hutang' => (float)$selisihAsetVsHutang,
                    'status_keamanan' => $statusKeamanan,
                    'tingkat_resiko' => $tingkatResiko,
                    'pesan_analisis' => $pesanAnalisis
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Stock Debt Monitor Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Audit Stok Lambat (Dead Stock)
     * Mengidentifikasi barang yang tidak laku terjual dalam kurun waktu tertentu.
     */
    public function getDeadStockAudit(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $days = (int)$request->input('days', 30);
            $cutoffDate = Carbon::now()->subDays($days)->format('Y-m-d H:i:s');

            // 1. Ambil agregat penjualan retail sparepart
            $salesQty = DB::table('detail_sparepart_penjualans')
                ->join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->where('penjualans.kode_owner', $kode_owner)
                ->where('penjualans.status_penjualan', '1')
                ->where('penjualans.created_at', '>=', $cutoffDate)
                ->select('detail_sparepart_penjualans.kode_sparepart', DB::raw('SUM(detail_sparepart_penjualans.qty_sparepart) as total_qty'))
                ->groupBy('detail_sparepart_penjualans.kode_sparepart')
                ->pluck('total_qty', 'detail_sparepart_penjualans.kode_sparepart')
                ->toArray();

            // 2. Ambil agregat part yang digunakan dalam service
            $serviceQty = DB::table('detail_part_services')
                ->join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->where('sevices.updated_at', '>=', $cutoffDate)
                ->select('detail_part_services.kode_sparepart', DB::raw('SUM(detail_part_services.qty_part) as total_qty'))
                ->groupBy('detail_part_services.kode_sparepart')
                ->pluck('total_qty', 'detail_part_services.kode_sparepart')
                ->toArray();

            // 3. Ambil semua sparepart dengan stok > 0
            $spareparts = Sparepart::withoutGlobalScope(ActiveScope::class)
                ->where('kode_owner', $kode_owner)
                ->where('stok_sparepart', '>', 0)
                ->with('kategori')
                ->get();

            $deadStock = [];
            $slowMoving = [];
            $totalValueDeadStock = 0;

            foreach ($spareparts as $sp) {
                $qtySold = ($salesQty[$sp->id] ?? 0) + ($serviceQty[$sp->id] ?? 0);
                $value = $sp->stok_sparepart * $sp->harga_beli;

                $item = [
                    'id' => $sp->id,
                    'kode_sparepart' => $sp->kode_sparepart,
                    'nama_sparepart' => $sp->nama_sparepart,
                    'kategori' => $sp->kategori->nama_kategori ?? 'N/A',
                    'stok' => (int)$sp->stok_sparepart,
                    'harga_beli' => (float)$sp->harga_beli,
                    'harga_jual' => (float)$sp->harga_jual,
                    'nilai_stok' => (float)$value,
                    'total_terjual_periode' => (int)$qtySold,
                ];

                if ($qtySold == 0) {
                    $deadStock[] = $item;
                    $totalValueDeadStock += $value;
                } else {
                    // Masukkan ke slow moving jika terjual sedikit dibanding stoknya
                    if ($qtySold <= 2 || ($qtySold / ($sp->stok_sparepart + $qtySold) < 0.15)) {
                        $slowMoving[] = $item;
                    }
                }
            }

            // Urutkan Dead Stock berdasarkan Nilai Stok terbesar
            usort($deadStock, function($a, $b) {
                return $b['nilai_stok'] <=> $a['nilai_stok'];
            });

            // Urutkan Slow Moving berdasarkan penjualan terendah lalu nilai stok terbesar
            usort($slowMoving, function($a, $b) {
                if ($a['total_terjual_periode'] === $b['total_terjual_periode']) {
                    return $b['nilai_stok'] <=> $a['nilai_stok'];
                }
                return $a['total_terjual_periode'] <=> $b['total_terjual_periode'];
            });

            return response()->json([
                'success' => true,
                'message' => 'Audit stok lambat berhasil diambil',
                'data' => [
                    'dead_stock' => $deadStock,
                    'slow_moving' => $slowMoving,
                    'summary' => [
                        'total_items_dead_stock' => count($deadStock),
                        'total_value_dead_stock' => $totalValueDeadStock,
                        'total_items_slow_moving' => count($slowMoving),
                        'audit_period_days' => $days
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Get Dead Stock Audit Error', [
                'user_id' => auth()->user()->id ?? null,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

}

