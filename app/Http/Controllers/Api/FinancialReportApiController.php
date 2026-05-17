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
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $kode_owner = $this->getThisUser()->id_upline;

            // ==============================================
            // 1. PROFIT ANALYSIS (ACCRUAL BASIS) - Using FinancialService
            // ==============================================
            // This ensures logic matches the Admin Dashboard & POSY reference
            $profitData = $this->financialService->calculateNetProfit($kode_owner, $tgl_awal, $tgl_akhir);

            // ==============================================
            // 2. CASH FLOW CALCULATION (CASH BASIS)
            // ==============================================
            // Still calculated manually because FinancialService is for Profit (Accrual), not Cash Flow.
            
            // Cash In
            $totalCashInService = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum(DB::raw('total_biaya - dp'));
                
            $totalCashInDP = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('dp');

            $totalCashInSales = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('total_penjualan');

            $totalCashInOther = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_pemasukkan');

            // Breakdown pemasukan lainnya cash dan transfer
            $totalCashInOtherCash = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_cash');

            $totalCashInOtherTransfer = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_transfer');

            // Uang real di laci dan bank dari penjualan, pengambilan, dan DP service
            $uangRealDiLaciPenjualan = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_cash');
            $uangRealDiBankPenjualan = Penjualan::where('kode_owner', $kode_owner)
                ->where('status_penjualan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_transfer');

            $uangRealDiLaciPengambilan = \App\Models\Pengambilan::where('kode_owner', $kode_owner)
                ->where('status_pengambilan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_cash');
            $uangRealDiBankPengambilan = \App\Models\Pengambilan::where('kode_owner', $kode_owner)
                ->where('status_pengambilan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_transfer');

            $uangRealDiLaciDP = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('dp_cash');
            $uangRealDiBankDP = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('dp_transfer');

            $totalCashIn = $totalCashInService + $totalCashInDP + $totalCashInSales + $totalCashInOther;

            // Cash Out
            $totalCashOutStore = PengeluaranToko::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_pengeluaran');

            $totalCashOutOperational = PengeluaranOperasional::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jml_pengeluaran');

            $totalCashOutWithdrawal = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_penarikan');

            // Breakdown penarikan cash dan transfer
            $totalCashOutWithdrawalCash = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_cash');

            $totalCashOutWithdrawalTransfer = Penarikan::where('kode_owner', $kode_owner)
                ->where('status_penarikan', '1')
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_transfer');

            // Hitung uang real di laci dan bank setelah dikurangi penarikan
                $uangRealDiLaci = $uangRealDiLaciPenjualan + $uangRealDiLaciPengambilan + $uangRealDiLaciDP - $totalCashOutWithdrawalCash + $totalCashInOtherCash;
                $uangRealDiBank = $uangRealDiBankPenjualan + $uangRealDiBankPengambilan + $uangRealDiBankDP - $totalCashOutWithdrawalTransfer + $totalCashInOtherTransfer;
            
            // NEW: Pembelian Stok (Tunai)
            // Status pembayaran 'Lunas' berarti bayar tunai/transfer saat pembelian
            // updated_at digunakan karena mencerminkan waktu finalize
            $totalPembelianTunai = Pembelian::where('kode_owner', $kode_owner)
                ->where('status', 'selesai')
                ->where('metode_pembayaran', '!=', 'Hutang')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('total_harga');

            // NEW: Pembayaran Hutang
            // Hutang yang statusnya 'Lunas' dan dibayar dalam periode ini
            $totalBayarHutang = Hutang::where('kode_owner', $kode_owner)
                ->where('status', 'Lunas')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('total_hutang');
            
            // Parts cash out approximation (This logic might overlap with Pembelian if not careful, 
            // but usually this tracks "COGS Cash Flow" if stock wasn't tracked. 
            // Since we now track Stock Purchase as Cash Out, do we remove this?
            // NO. The user previously had this. 
            // WAIT. If we count "Pembelian Stok" as cash out, and "Part Cost" as cash out, we are double counting?
            // "Pembelian Stok" is when we BUY from supplier.
            // "Part Cost" in Service is when we SELL/USE the part. This is NOT a cash outflow at that moment (it was outflow when bought).
            // SO, for CASH FLOW report, "Part Cost" should NOT be here if we are tracking "Pembelian".
            // HOWEVER, previous logic used "Part Cost" as a proxy for expense because "Pembelian" wasn't tracked.
            // DECISION: To be accurate for CASH FLOW, we should use 'Pembelian' (money out) and remove 'Part Cost' (inventory usage).
            // BUT, to avoid confusing the user with a sudden drop in expenses if they don't do 'Pembelian' correctly yet,
            // I will KEEP 'Part Cost' BUT label it clearly or maybe better:
            // "Arus Kas" should be real money out. Real money out is Pembelian. Usage of part is Inventory -> COGS (Profit/Loss).
            // Let's replace "Part Cost" with "Pembelian" & "Bayar Hutang" for the CASH OUT section.
            // But wait, "Part Luar" (bought specifically for service) IS a cash out at that moment usually.
            
            // Let's refine:
            // Part Toko: Bought via Pembelian (Stock). Cash out happens at Pembelian. Usage is not cash out.
            // Part Luar: Bought ad-hoc. Usually cash out immediately.
            
            // So:
            // Cash Out = Pengeluaran Toko + Ops + Penarikan + Pembelian Stok + Bayar Hutang + Part Luar (Direct Purchase).
            // Part Toko usage should NOT be in Cash Flow (it's in Profit/Loss as COGS).
            
            $totalCashOutPartsLuar = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));
            
            // Total Cash Out Updated
            // EXCLUDE Pembelian Stok (Tunai) & Bayar Hutang from "Total Uang Keluar" (Operational Flow)
            // User feedback: Stock purchases & Debt payments use "Kas Toko" (Safe), not "Kas Laci" (Daily Flow).
            // Including them causes "Saldo Kas" to be negative, which confuses the daily evaluation.
            $totalCashOut = $totalCashOutStore + 
                           $totalCashOutOperational + 
                           $totalCashOutWithdrawal + 
                           // $totalPembelianTunai + // Excluded from Operational Cash Out
                           // $totalBayarHutang + // Excluded from Operational Cash Out
                           $totalCashOutPartsLuar;

            $netCashFlow = $totalCashIn - $totalCashOut;

             // 3. Service Stats
            $serviceStats = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->selectRaw('COUNT(*) as total, SUM(CASE WHEN total_biaya < 0 THEN 1 ELSE 0 END) as rugi_count')
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
                'analisis_part_toko' => $this->getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir), // Restored
                
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
            
            $stats = $this->_getDashboardStats($kode_owner, $today);

            // Add extra info for mobile dashboard
            $stats['recent_completed_services'] = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->select('kode_service', 'nama_pelanggan', 'total_biaya', 'updated_at')
                ->get();

            $stats['pending_services_count'] = Sevices::where('kode_owner', $kode_owner)
                ->whereIn('status_services', ['Antri', 'Proses'])
                ->count();

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
    private function _getDashboardStats(int $ownerId, string $date): array
    {
        // 1. Profit Summary (Operational)
        $labaResult = $this->calculateNetProfit($ownerId, $date, $date);
        
        $totalRevenue = $labaResult['laba_kotor'] + ($labaResult['detail_beban']['HPP (Modal Pokok Penjualan)'] ?? 0);
        
        $operatingExpenses = ($labaResult['detail_beban']['Biaya Operasional Insidental'] ?? 0) 
                             + ($labaResult['detail_beban']['Biaya Komisi Teknisi'] ?? 0)
                             + ($labaResult['detail_beban']['Beban Tetap Periodik'] ?? 0);
                             
        $depreciation = $labaResult['detail_beban']['Beban Penyusutan Aset'] ?? 0;
        $netProfit = $labaResult['laba_bersih'];

        // 2. Inventory Value (ASSET)
        $nilaiSparepart = Sparepart::withoutGlobalScope(ActiveScope::class)->where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
        $nilaiHandphone = Handphone::withoutGlobalScope(ActiveScope::class)->where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
        $inventoryValue = $nilaiSparepart + $nilaiHandphone;

        // 3. Saldo Kas (ASSET) - Source of Truth: KasPerusahaan Ledger
        $endDate = Carbon::parse($date)->endOfDay();
        $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)
            ->where('tanggal', '<=', $endDate)
            ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')
            ->first()->saldo ?? 0;

        // 4. Modal Disetor (Paid In Capital)
        $capitalIn = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
            ->whereIn('jenis_transaksi', ['setoran_awal', 'tambahan_modal'])
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');

        $capitalOut = TransaksiModal::where('kode_owner', $ownerId)
            ->where('jenis_transaksi', 'penarikan_modal')
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');
            
        $paidInCapital = $capitalIn - $capitalOut;

        // 5. Total Asset
        $asetTetap = Aset::where('kode_owner', $ownerId)
             ->where('tanggal_perolehan', '<=', $endDate)
             ->sum('nilai_perolehan');
             
        $totalAsset = $saldoKas + $inventoryValue + $asetTetap;

        return [
            'revenue' => $totalRevenue,
            'expense' => $operatingExpenses + $depreciation,
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
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $kode_owner = $this->getThisUser()->id_upline;

            Log::info('Service Loss Details Request', [
                'user_id' => auth()->user()->id,
                'period' => $tgl_awal . ' to ' . $tgl_akhir
            ]);

            // Ambil semua service dalam periode
            $services = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get();

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
                'type' => 'required|in:service,penjualan,pemasukkan_lain,pengeluaran,penarikan,part_service,pengeluaran_toko,pengeluaran_operasional,pembelian_tunai,pembayaran_hutang'
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $type = $request->type;
            $kode_owner = $this->getThisUser()->id_upline;

            Log::info('Detailed Report Request', [
                'user_id' => auth()->user()->id,
                'type' => $type,
                'period' => $tgl_awal . ' to ' . $tgl_akhir
            ]);

            $data = [];

            switch ($type) {
                case 'service':
                    $data = $this->getServiceDetails($kode_owner, $tgl_awal, $tgl_akhir);
                    break;

                case 'penjualan':
                    $data = $this->getSalesDetails($kode_owner, $tgl_awal, $tgl_akhir);
                    break;

                case 'pemasukkan_lain':
                    $data = $this->getOtherIncomeDetails($kode_owner, $tgl_awal, $tgl_akhir);
                    break;

                // BARU: Detail Part Service
                case 'part_service':
                    $data = $this->getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir);
                    break;

                // PERBAIKAN: Pisahkan pengeluaran toko dan operasional
                case 'pengeluaran_toko':
                    $data = PengeluaranToko::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->select('nama_pengeluaran as judul', 'catatan_pengeluaran as catatan', 'jumlah_pengeluaran as jumlah', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Toko';
                            return $item;
                        });
                    break;

                case 'pengeluaran_operasional':
                    $data = PengeluaranOperasional::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->select('nama_pengeluaran as judul', 'desc_pengeluaran as catatan', 'jml_pengeluaran as jumlah', 'created_at')
                        ->orderBy('created_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Operasional';
                            return $item;
                        });
                        break;
                    break;

                case 'pembelian_tunai':
                     $data = Pembelian::where('kode_owner', $kode_owner)
                        ->where('status', 'selesai')
                        ->where('metode_pembayaran', '!=', 'Hutang')
                        ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->select('kode_pembelian as judul', 'supplier as catatan', 'total_harga as jumlah', 'updated_at as created_at')
                        ->orderBy('updated_at', 'desc')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pembelian Tunai';
                            return $item;
                        });
                    break;
                
                case 'pembayaran_hutang':
                     $data = Hutang::where('kode_owner', $kode_owner)
                        ->where('status', 'Lunas')
                        ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->with('supplier')
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

                // BACKWARD COMPATIBILITY: Gabungan pengeluaran
                case 'pengeluaran':
                    $pengeluaranToko = PengeluaranToko::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->select('nama_pengeluaran as judul', 'catatan_pengeluaran as catatan', 'jumlah_pengeluaran as jumlah', 'created_at')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Toko';
                            return $item;
                        });

                    $pengeluaranOps = PengeluaranOperasional::where('kode_owner', $kode_owner)
                        ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                        ->select('nama_pengeluaran as judul', 'desc_pengeluaran as catatan', 'jml_pengeluaran as jumlah', 'created_at')
                        ->get()
                        ->map(function ($item) {
                            $item->type = 'Pengeluaran Operasional';
                            return $item;
                        });

                    $data = $pengeluaranToko->concat($pengeluaranOps)->sortByDesc('created_at')->values();
                    break;

                case 'penarikan':
                    $data = $this->getWithdrawalDetails($kode_owner, $tgl_awal, $tgl_akhir);
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
    private function getServiceDetails($kode_owner, $tgl_awal, $tgl_akhir)
    {
        return Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select('kode_service', 'nama_pelanggan', 'type_unit', 'total_biaya', 'dp', 'updated_at')
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
    private function getPartServiceDetails($kode_owner, $tgl_awal, $tgl_akhir)
    {
        $partTokoDetails = DB::table('detail_part_services')
            ->join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
            ->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select(
                'sevices.kode_service',
                'sevices.nama_pelanggan',
                'spareparts.nama_sparepart',
                'detail_part_services.detail_harga_part_service as harga_jual',
                // PENTING: Ambil harga modal historis jika ada, fallback ke master
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

        $partLuarDetails = DB::table('detail_part_luar_services')
            ->join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select(
                'sevices.kode_service',
                'sevices.nama_pelanggan',
                'detail_part_luar_services.nama_part as nama_sparepart',
                'detail_part_luar_services.harga_part as harga_jual',
                'detail_part_luar_services.harga_part as harga_modal', // Part luar = harga = modal
                'detail_part_luar_services.qty_part as qty',
                'sevices.updated_at',
                DB::raw("'Part Luar' as source_type")
            )
            ->get()
            ->map(function($item) {
                $totalJual = $item->harga_jual * $item->qty;
                $totalModal = $item->harga_modal * $item->qty;
                $margin = 0; // Part luar tidak ada margin

                $item->total_harga_jual = $totalJual;
                $item->total_harga_modal = $totalModal;
                $item->margin_keuntungan = $margin;
                $item->margin_persen = 0;

                return $item;
            });

        // Gabungkan dan urutkan
        return $partTokoDetails->concat($partLuarDetails)
            ->sortByDesc('updated_at')
            ->values();
    }

    private function getSalesDetails($kode_owner, $tgl_awal, $tgl_akhir)
    {
        return Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select('kode_penjualan', 'nama_customer', 'total_penjualan', 'total_bayar', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getOtherIncomeDetails($kode_owner, $tgl_awal, $tgl_akhir)
    {
        return PemasukkanLain::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select('judul_pemasukan', 'catatan_pemasukkan', 'jumlah_pemasukkan', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    private function getWithdrawalDetails($kode_owner, $tgl_awal, $tgl_akhir)
    {
        return Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')
            ->where('penarikans.kode_owner', $kode_owner)
            ->where('penarikans.status_penarikan', '1')
            ->whereBetween('penarikans.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
            ->select(
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
            ]);

            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;
            $kode_owner = $this->getThisUser()->id_upline;

            // Validasi rentang tanggal tidak lebih dari 90 hari
            $daysDiff = Carbon::parse($tgl_awal)->diffInDays(Carbon::parse($tgl_akhir));
            if ($daysDiff > 90) {
                return response()->json([
                    'success' => false,
                    'message' => 'Rentang tanggal tidak boleh lebih dari 90 hari'
                ], 422);
            }

            Log::info('Daily Report Request', [
                'user_id' => auth()->user()->id,
                'period' => $tgl_awal . ' to ' . $tgl_akhir,
                'days_count' => $daysDiff + 1
            ]);

            $dailyData = [];
            $currentDate = Carbon::parse($tgl_awal);
            $endDate = Carbon::parse($tgl_akhir);

            while ($currentDate <= $endDate) {
                $dateStr = $currentDate->format('Y-m-d');

                // Pendapatan harian
                $serviceIncome = Sevices::where('kode_owner', $kode_owner)
                    ->where('status_services', 'Diambil')
                    ->whereDate('updated_at', $dateStr)
                    ->sum(DB::raw('total_biaya - dp'));

                $dpIncome = Sevices::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->sum('dp');

                $salesIncome = Penjualan::where('kode_owner', $kode_owner)
                    ->where('status_penjualan', '1')
                    ->whereDate('created_at', $dateStr)
                    ->sum('total_penjualan');

                $otherIncome = PemasukkanLain::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->sum('jumlah_pemasukkan');

                // Pengeluaran harian
                // Part Luar Only for Cash Flow
                $partLuarExpense = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                    ->where('sevices.kode_owner', $kode_owner)
                    ->where('sevices.status_services', 'Diambil')
                    ->whereDate('sevices.updated_at', $dateStr)
                    ->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));

                // Part Toko removed from cash flow daily, but if we want to keep backward compat, maybe not?
                // No, let's align with getFinancialReport.
                
                $storeExpense = PengeluaranToko::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->sum('jumlah_pengeluaran');

                $operationalExpense = PengeluaranOperasional::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->whereNull('beban_operasional_id') // EXCLUDE Sinking Fund Payments (Allocated Daily)
                    ->sum('jml_pengeluaran');

                $withdrawalExpense = Penarikan::where('kode_owner', $kode_owner)
                    ->where('status_penarikan', '1')
                    ->whereDate('created_at', $dateStr)
                    ->sum('jumlah_penarikan');
                
                // NEW: Pembelian Tunai
                $purchaseExpense = Pembelian::where('kode_owner', $kode_owner)
                    ->where('status', 'selesai')
                    ->where('metode_pembayaran', '!=', 'Hutang')
                    ->whereDate('updated_at', $dateStr)
                    ->sum('total_harga');

                // NEW: Bayar Hutang
                $debtPaymentExpense = Hutang::where('kode_owner', $kode_owner)
                    ->where('status', 'Lunas')
                    ->whereDate('updated_at', $dateStr)
                    ->sum('total_hutang');

                $totalIncome = $serviceIncome + $dpIncome + $salesIncome + $otherIncome;
                $totalExpense = $partLuarExpense + $storeExpense + $operationalExpense + $withdrawalExpense + $purchaseExpense + $debtPaymentExpense;
                $netProfit = $totalIncome - $totalExpense; // This is actually Net Cash Flow

                // Hitung jumlah transaksi
                $serviceCount = Sevices::where('kode_owner', $kode_owner)
                    ->where('status_services', 'Diambil')
                    ->whereDate('updated_at', $dateStr)
                    ->count();

                $salesCount = Penjualan::where('kode_owner', $kode_owner)
                    ->where('status_penjualan', '1')
                    ->whereDate('created_at', $dateStr)
                    ->count();

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
                        'parts_luar' => (float) $partLuarExpense, // Renamed from parts
                        'store' => (float) $storeExpense,
                        'operational' => (float) $operationalExpense,
                        'withdrawal' => (float) $withdrawalExpense,
                        'purchase' => (float) $purchaseExpense, // New
                        'debt_payment' => (float) $debtPaymentExpense, // New
                        'total' => (float) $totalExpense
                    ],
                    'net_profit' => (float) $netProfit, // Net Cash Flow
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
            ]);

            $kode_owner = $this->getThisUser()->id_upline;
            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;

            // Ambil data profit per teknisi dari tabel profit_presentases
            $technicianProfits = DB::table('profit_presentases')
                ->join('sevices', 'profit_presentases.kode_service', '=', 'sevices.id')
                ->join('user_details', 'profit_presentases.kode_user', '=', 'user_details.kode_user')
                ->where('sevices.kode_owner', $kode_owner)
                ->whereBetween('profit_presentases.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->select(
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

}
