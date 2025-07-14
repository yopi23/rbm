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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class FinancialReportApiController extends Controller
{
    /**
     * Get comprehensive financial report
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
            // CASH FLOW CALCULATION
            // ==============================================

            $totalCashInService = 0;
            $totalCashInDP = 0;
            $totalCashInSales = 0;
            $totalCashInOther = 0;

            $totalCashOutParts = 0;
            $totalCashOutStore = 0;
            $totalCashOutOperational = 0;
            $totalCashOutWithdrawal = 0;

            // ==============================================
            // PROFIT CALCULATION (Yang Benar)
            // ==============================================

            $totalServiceProfit = 0;
            $totalSalesProfit = 0;
            $totalPartTokoProfit = 0; // BARU: Profit dari penjualan part internal

            // NEW: Hitung total part toko yang belum dibayar fisik
            $totalPartTokoNotPaidPhysically = 0;

            // 1. ANALISIS SERVICE PROFIT
            $serviceData = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get();

            foreach ($serviceData as $service) {
                // Cash In dari service
                $serviceCashIn = $service->total_biaya - $service->dp;
                $totalCashInService += $serviceCashIn;

                // Hitung PENGELUARAN PARTS untuk service (harga jual, bukan modal)
                $totalPartsExpense = 0;

                // Parts dari toko - gunakan HARGA JUAL sebagai pengeluaran service
                $partsToko = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->where('detail_part_services.kode_services', $service->id)
                    ->select(
                        'spareparts.harga_beli',
                        'detail_part_services.qty_part',
                        'detail_part_services.detail_harga_part_service'
                    )
                    ->get();

                foreach ($partsToko as $part) {
                    // Untuk SERVICE: pengeluaran = harga jual part
                    $partExpense = $part->detail_harga_part_service * $part->qty_part;
                    $totalPartsExpense += $partExpense;

                    // Untuk PENJUALAN INTERNAL: hitung profit part toko
                    $partModal = $part->harga_beli * $part->qty_part;
                    $partProfit = $partExpense - $partModal;
                    $totalPartTokoProfit += $partProfit;

                    // NEW: Tambahkan ke total part toko yang belum dibayar fisik
                    $totalPartTokoNotPaidPhysically += $partExpense;
                }

                // Parts dari luar - tetap gunakan harga penuh
                $partsLuar = DetailPartLuarService::where('kode_services', $service->id)->get();
                foreach ($partsLuar as $part) {
                    $totalPartsExpense += ($part->harga_part * $part->qty_part);
                }

                // SERVICE PROFIT = Total Biaya Service - Total Pengeluaran Parts
                $serviceProfit = $service->total_biaya - $totalPartsExpense;
                $totalServiceProfit += $serviceProfit;

                // Debug log untuk service yang rugi
                if ($serviceProfit < 0) {
                    Log::warning('Service Rugi Detected', [
                        'kode_service' => $service->kode_service,
                        'total_biaya' => $service->total_biaya,
                        'total_parts_expense' => $totalPartsExpense,
                        'profit_loss' => $serviceProfit
                    ]);
                }
            }

            // 2. DP SERVICE (Cash In)
            $serviceDpData = Sevices::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get();

            foreach ($serviceDpData as $service) {
                $totalCashInDP += $service->dp;
            }

            // 3. SALES PROFIT CALCULATION (Penjualan Eksternal)
            $salesData = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->where('penjualans.kode_owner', $kode_owner)
                ->where('penjualans.status_penjualan', '1')
                ->whereBetween('penjualans.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get();

            foreach ($salesData as $item) {
                $salesAmount = $item->detail_harga_jual * $item->qty_sparepart;
                $salesModal = $item->detail_harga_modal * $item->qty_sparepart;

                $totalCashInSales += $salesAmount;
                $totalSalesProfit += ($salesAmount - $salesModal);
            }

            // Sales Barang
            $salesBarangData = DetailBarangPenjualan::join('penjualans', 'detail_barang_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->where('penjualans.kode_owner', $kode_owner)
                ->where('penjualans.status_penjualan', '1')
                ->whereBetween('penjualans.created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get();

            foreach ($salesBarangData as $item) {
                $salesAmount = $item->detail_harga_jual * $item->qty_barang;
                $salesModal = $item->detail_harga_beli * $item->qty_barang;

                $totalCashInSales += $salesAmount;
                $totalSalesProfit += ($salesAmount - $salesModal);
            }

            // 4. PEMASUKKAN LAIN
            $totalCashInOther = PemasukkanLain::where('kode_owner', $kode_owner)
                ->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('jumlah_pemasukkan');

            // 5. CASH OUT - PARTS (untuk cash flow menggunakan harga jual)
            $totalCashOutPartsToko = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('detail_part_services.detail_harga_part_service');

            $totalCashOutPartsLuar = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));

            $totalCashOutParts = $totalCashOutPartsToko + $totalCashOutPartsLuar;

            // 6. CASH OUT - LAINNYA
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

            // ==============================================
            // FINAL CALCULATIONS
            // ==============================================

            // CASH FLOW
            $totalCashIn = $totalCashInService + $totalCashInDP + $totalCashInSales + $totalCashInOther;
            $totalCashOut = $totalCashOutParts + $totalCashOutStore + $totalCashOutOperational + $totalCashOutWithdrawal;
            $netCashFlow = $totalCashIn - $totalCashOut;

            // NEW: UANG REAL DI LACI = Saldo Kas - Part Toko yang belum dibayar fisik
            $uangRealDiLaci = $totalCashIn - $totalCashOutWithdrawal;

            // PROFIT ANALYSIS
            // Total profit penjualan = penjualan eksternal + penjualan internal (part toko)
            $totalPenjualanProfit = $totalSalesProfit + $totalPartTokoProfit;

            $totalGrossProfit = $totalServiceProfit + $totalPenjualanProfit + $totalCashInOther;
            $totalOperatingExpenses = $totalCashOutStore + $totalCashOutOperational;
            $netBusinessProfit = $totalGrossProfit - $totalOperatingExpenses;

            // Hitung statistik part toko
            $totalPartTokoSales = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum('detail_part_services.detail_harga_part_service');

            $totalPartTokoModal = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                ->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                ->where('sevices.kode_owner', $kode_owner)
                ->where('sevices.status_services', 'Diambil')
                ->whereBetween('sevices.updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->sum(DB::raw('spareparts.harga_beli * detail_part_services.qty_part'));

            $persentaseMarginPartToko = $totalPartTokoSales > 0 ?
                ($totalPartTokoProfit / $totalPartTokoSales) * 100 : 0;

            // Hitung jumlah service yang rugi (dengan perhitungan yang benar)
            $serviceRugiCount = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])
                ->get()
                ->filter(function($service) {
                    $totalPartsExpense = 0;

                    // Hitung pengeluaran parts (harga jual)
                    $partsToko = DetailPartServices::where('kode_services', $service->id)->get();
                    foreach ($partsToko as $part) {
                        $totalPartsExpense += $part->detail_harga_part_service;
                    }

                    $partsLuar = DetailPartLuarService::where('kode_services', $service->id)->get();
                    foreach ($partsLuar as $part) {
                        $totalPartsExpense += ($part->harga_part * $part->qty_part);
                    }

                    return ($service->total_biaya - $totalPartsExpense) < 0;
                })->count();

            // Response data
            $reportData = [
                // CASH FLOW
                'total_pendapatan' => $totalCashIn,
                'total_pengeluaran' => $totalCashOut,
                'saldo_kas' => $netCashFlow,

                // NEW: UANG REAL DI LACI
                'uang_real_di_laci' => $uangRealDiLaci,
                'part_toko_belum_dibayar' => $totalPartTokoNotPaidPhysically,

                // CASH FLOW DETAIL
                'total_pendapatan_service' => $totalCashInService,
                'dp_service' => $totalCashInDP,
                'total_penjualan' => $totalCashInSales,
                'total_pemasukkan_lain' => $totalCashInOther,
                'total_part_service' => $totalCashOutParts,
                'total_pengeluaran_toko' => $totalCashOutStore,
                'total_pengeluaran_operasional' => $totalCashOutOperational,
                'total_penarikan' => $totalCashOutWithdrawal,

                // PROFIT ANALYSIS (YANG BENAR)
                'laba_service' => $totalServiceProfit,
                'laba_penjualan' => $totalPenjualanProfit, // Eksternal + Internal (part toko)
                'laba_penjualan_eksternal' => $totalSalesProfit, // Penjualan biasa
                'laba_penjualan_internal' => $totalPartTokoProfit, // Part toko ke service
                'total_laba_kotor' => $totalGrossProfit,
                'total_beban_operasional' => $totalOperatingExpenses,
                'laba_bersih_bisnis' => $netBusinessProfit,

                // ANALISIS PART TOKO (PENJUALAN INTERNAL)
                'analisis_part_toko' => [
                    'total_penjualan_part_toko' => $totalPartTokoSales,
                    'total_modal_part_toko' => $totalPartTokoModal,
                    'total_margin_part_toko' => $totalPartTokoProfit,
                    'persentase_margin_part_toko' => $persentaseMarginPartToko,
                ],

                // ANALISIS TAMBAHAN
                'service_rugi_count' => $serviceRugiCount,
                'service_profit_count' => $serviceData->count() - $serviceRugiCount,
                'average_service_profit' => $serviceData->count() > 0 ? $totalServiceProfit / $serviceData->count() : 0,

                // BACKWARD COMPATIBILITY
                'laba_bersih' => $netCashFlow,

                // META
                'periode' => [
                    'tgl_awal' => $tgl_awal,
                    'tgl_akhir' => $tgl_akhir,
                    'jumlah_hari' => Carbon::parse($tgl_awal)->diffInDays(Carbon::parse($tgl_akhir)) + 1
                ]
            ];

            Log::info('Financial Report with Real Cash Calculation', [
                'user_id' => auth()->user()->id,
                'total_service_profit' => $totalServiceProfit,
                'total_sales_profit_external' => $totalSalesProfit,
                'total_sales_profit_internal' => $totalPartTokoProfit,
                'service_rugi_count' => $serviceRugiCount,
                'net_cash_flow' => $netCashFlow,
                'uang_real_di_laci' => $uangRealDiLaci,
                'part_toko_belum_dibayar' => $totalPartTokoNotPaidPhysically,
                'net_business_profit' => $netBusinessProfit
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Laporan keuangan berhasil diambil',
                'data' => $reportData
            ], 200);

        } catch (\Exception $e) {
            Log::error('Financial Report Error', [
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
                'type' => 'required|in:service,penjualan,pemasukkan_lain,pengeluaran,penarikan,part_service,pengeluaran_toko,pengeluaran_operasional'
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
                'spareparts.harga_beli as harga_modal', // PENTING: Ambil harga modal
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
                $partExpense = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
                    ->where('sevices.kode_owner', $kode_owner)
                    ->where('sevices.status_services', 'Diambil')
                    ->whereDate('sevices.updated_at', $dateStr)
                    ->sum('detail_part_services.detail_harga_part_service');

                $partLuarExpense = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                    ->where('sevices.kode_owner', $kode_owner)
                    ->where('sevices.status_services', 'Diambil')
                    ->whereDate('sevices.updated_at', $dateStr)
                    ->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));

                $totalPartExpense = $partExpense + $partLuarExpense;

                $storeExpense = PengeluaranToko::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->sum('jumlah_pengeluaran');

                $operationalExpense = PengeluaranOperasional::where('kode_owner', $kode_owner)
                    ->whereDate('created_at', $dateStr)
                    ->sum('jml_pengeluaran');

                $withdrawalExpense = Penarikan::where('kode_owner', $kode_owner)
                    ->where('status_penarikan', '1')
                    ->whereDate('created_at', $dateStr)
                    ->sum('jumlah_penarikan');

                $totalIncome = $serviceIncome + $dpIncome + $salesIncome + $otherIncome;
                $totalExpense = $totalPartExpense + $storeExpense + $operationalExpense + $withdrawalExpense;
                $netProfit = $totalIncome - $totalExpense;

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
                        'parts' => (float) $totalPartExpense,
                        'store' => (float) $storeExpense,
                        'operational' => (float) $operationalExpense,
                        'withdrawal' => (float) $withdrawalExpense,
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
     * Get financial summary for dashboard
     */
    public function getFinancialSummary(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $today = Carbon::today();
            $thisMonth = Carbon::now()->startOfMonth();
            $thisYear = Carbon::now()->startOfYear();

            // Summary hari ini
            $todaySummary = $this->calculatePeriodSummary($kode_owner, $today, $today);

            // Summary bulan ini
            $monthSummary = $this->calculatePeriodSummary($kode_owner, $thisMonth, $today);

            // Summary tahun ini
            $yearSummary = $this->calculatePeriodSummary($kode_owner, $thisYear, $today);

            // Top 5 service terbaru yang selesai
            $recentCompletedServices = Sevices::where('kode_owner', $kode_owner)
                ->where('status_services', 'Diambil')
                ->orderBy('updated_at', 'desc')
                ->take(5)
                ->select('kode_service', 'nama_pelanggan', 'total_biaya', 'updated_at')
                ->get();

            // Pending services count
            $pendingServicesCount = Sevices::where('kode_owner', $kode_owner)
                ->whereIn('status_services', ['Antri', 'Proses'])
                ->count();

            return response()->json([
                'success' => true,
                'message' => 'Ringkasan keuangan berhasil diambil',
                'data' => [
                    'today' => $todaySummary,
                    'this_month' => $monthSummary,
                    'this_year' => $yearSummary,
                    'recent_completed_services' => $recentCompletedServices,
                    'pending_services_count' => $pendingServicesCount,
                    'generated_at' => Carbon::now()->toISOString()
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error('Financial Summary Error', [
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
     * Helper method to calculate period summary
     */
    private function calculatePeriodSummary($kode_owner, $startDate, $endDate)
    {
        $startDateStr = $startDate->format('Y-m-d') . ' 00:00:00';
        $endDateStr = $endDate->format('Y-m-d') . ' 23:59:59';

        // Pendapatan
        $serviceIncome = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startDateStr, $endDateStr])
            ->sum(DB::raw('total_biaya - dp'));

        $dpIncome = Sevices::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('dp');

        $salesIncome = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('total_penjualan');

        $otherIncome = PemasukkanLain::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('jumlah_pemasukkan');

        // Pengeluaran
        $partExpense = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$startDateStr, $endDateStr])
            ->sum('detail_part_services.detail_harga_part_service');

        $partLuarExpense = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
            ->where('sevices.kode_owner', $kode_owner)
            ->where('sevices.status_services', 'Diambil')
            ->whereBetween('sevices.updated_at', [$startDateStr, $endDateStr])
            ->sum(DB::raw('detail_part_luar_services.harga_part * detail_part_luar_services.qty_part'));

        $storeExpense = PengeluaranToko::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('jumlah_pengeluaran');

        $operationalExpense = PengeluaranOperasional::where('kode_owner', $kode_owner)
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('jml_pengeluaran');

        $withdrawalExpense = Penarikan::where('kode_owner', $kode_owner)
            ->where('status_penarikan', '1')
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->sum('jumlah_penarikan');

        $totalIncome = $serviceIncome + $dpIncome + $salesIncome + $otherIncome;
        $totalExpense = $partExpense + $partLuarExpense + $storeExpense + $operationalExpense + $withdrawalExpense;
        $netProfit = $totalIncome - $totalExpense;

        // Hitung jumlah transaksi
        $serviceCount = Sevices::where('kode_owner', $kode_owner)
            ->where('status_services', 'Diambil')
            ->whereBetween('updated_at', [$startDateStr, $endDateStr])
            ->count();

        $salesCount = Penjualan::where('kode_owner', $kode_owner)
            ->where('status_penjualan', '1')
            ->whereBetween('created_at', [$startDateStr, $endDateStr])
            ->count();

        return [
            'total_income' => (float) $totalIncome,
            'total_expense' => (float) $totalExpense,
            'net_profit' => (float) $netProfit,
            'service_income' => (float) $serviceIncome,
            'dp_income' => (float) $dpIncome,
            'sales_income' => (float) $salesIncome,
            'other_income' => (float) $otherIncome,
            'part_expense' => (float) ($partExpense + $partLuarExpense),
            'store_expense' => (float) $storeExpense,
            'operational_expense' => (float) $operationalExpense,
            'withdrawal_expense' => (float) $withdrawalExpense,
            'transaction_count' => [
                'service' => $serviceCount,
                'sales' => $salesCount
            ]
        ];
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

    //report service
/**
 * Get device statistics report with custom date range
 * Shows completed devices by type with revenue breakdown
 */
    public function getDeviceStatistics(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'group_by' => 'sometimes|in:day,week,month', // Optional grouping
                'limit' => 'sometimes|integer|min:1|max:100',
                'sort_by' => 'sometimes|in:count,revenue,avg_revenue',
                'sort_order' => 'sometimes|in:asc,desc'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->tgl_awal;
            $endDate = $request->tgl_akhir;
            $groupBy = $request->get('group_by', null);
            $limit = $request->get('limit', 50);
            $sortBy = $request->get('sort_by', 'revenue');
            $sortOrder = $request->get('sort_order', 'desc');

            // Get user's owner ID
            $kodeOwner = $this->getKodeOwner();

            // Cache key for the query
            $cacheKey = "device_statistics_{$kodeOwner}_{$startDate}_{$endDate}_{$groupBy}_{$sortBy}_{$sortOrder}_{$limit}";

            $result = Cache::remember($cacheKey, 300, function () use ($kodeOwner, $startDate, $endDate, $groupBy, $limit, $sortBy, $sortOrder) {

                // Base query for completed services in date range
                $baseQuery = DB::table('sevices')
                    ->where('kode_owner', $kodeOwner)
                    ->where('status_services', 'Selesai')
                    ->whereBetween('sevices.updated_at', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);

                // Get device type statistics
                $deviceStatsQuery = clone $baseQuery;
                $deviceStats = $deviceStatsQuery
                    ->select([
                        'type_unit',
                        DB::raw('COUNT(*) as total_services'),
                        DB::raw('SUM(total_biaya) as total_revenue'),
                        DB::raw('AVG(total_biaya) as avg_revenue'),
                        DB::raw('SUM(dp) as total_dp'),
                        DB::raw('SUM(total_biaya - dp) as total_remaining'),
                        DB::raw('MIN(total_biaya) as min_revenue'),
                        DB::raw('MAX(total_biaya) as max_revenue'),
                        DB::raw('SUM(harga_sp) as total_parts_cost')
                    ])
                    ->groupBy('type_unit')
                    ->orderBy($this->getSortColumn($sortBy), $sortOrder)
                    ->limit($limit)
                    ->get();

                // Calculate profit margin for each device type
                $deviceStats = $deviceStats->map(function ($item) {
                    $item->profit_margin = $item->total_revenue > 0
                        ? round((($item->total_revenue - $item->total_parts_cost) / $item->total_revenue) * 100, 2)
                        : 0;
                    $item->avg_revenue = round($item->avg_revenue, 2);
                    $item->completion_rate = round(($item->total_services / max($item->total_services, 1)) * 100, 2);
                    return $item;
                });

                // Get daily/weekly/monthly breakdown if requested
                $timeSeriesData = null;
                if ($groupBy) {
                    $timeSeriesQuery = clone $baseQuery;
                    $timeSeriesData = $this->getTimeSeriesData($timeSeriesQuery, $groupBy);
                }

                // Get overall summary
                $summaryQuery = clone $baseQuery;
                $summary = $summaryQuery
                    ->select([
                        DB::raw('COUNT(*) as total_services'),
                        DB::raw('COUNT(DISTINCT type_unit) as unique_device_types'),
                        DB::raw('SUM(total_biaya) as total_revenue'),
                        DB::raw('AVG(total_biaya) as avg_revenue_per_service'),
                        DB::raw('SUM(dp) as total_dp_collected'),
                        DB::raw('SUM(total_biaya - dp) as total_remaining_payments'),
                        DB::raw('SUM(harga_sp) as total_parts_cost')
                    ])
                    ->first();

                // Calculate additional metrics
                $summary->total_profit = $summary->total_revenue - $summary->total_parts_cost;
                $summary->overall_profit_margin = $summary->total_revenue > 0
                    ? round(($summary->total_profit / $summary->total_revenue) * 100, 2)
                    : 0;
                $summary->avg_revenue_per_service = round($summary->avg_revenue_per_service, 2);
                $summary->dp_collection_rate = $summary->total_revenue > 0
                    ? round(($summary->total_dp_collected / $summary->total_revenue) * 100, 2)
                    : 0;

                // Get top performing technicians for this period
                $topTechniciansQuery = clone $baseQuery;
                $topTechnicians = $topTechniciansQuery
                    ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                    ->select([
                        'users.name as teknisi_name',
                        'users.id as teknisi_id',
                        DB::raw('COUNT(*) as services_completed'),
                        DB::raw('SUM(sevices.total_biaya) as total_revenue'),
                        DB::raw('AVG(sevices.total_biaya) as avg_revenue'),
                        DB::raw('COUNT(DISTINCT sevices.type_unit) as device_types_handled')
                    ])
                    ->groupBy('users.id', 'users.name')
                    ->orderByDesc('total_revenue')
                    ->limit(10)
                    ->get();

                return [
                    'device_statistics' => $deviceStats,
                    'summary' => $summary,
                    'time_series' => $timeSeriesData,
                    'top_technicians' => $topTechnicians,
                    'metadata' => [
                        'date_range' => [
                            'start' => $startDate,
                            'end' => $endDate,
                            'days' => Carbon::parse($startDate)->diffInDays(Carbon::parse($endDate)) + 1
                        ],
                        'group_by' => $groupBy,
                        'sort_by' => $sortBy,
                        'sort_order' => $sortOrder,
                        'limit' => $limit
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Device statistics retrieved successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Get Device Statistics Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device statistics',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get device trends over time
     */
    public function getDeviceTrends(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
                'device_types' => 'sometimes|array',
                'device_types.*' => 'string',
                'interval' => 'sometimes|in:day,week,month'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $startDate = $request->tgl_awal;
            $endDate = $request->tgl_akhir;
            $deviceTypes = $request->get('device_types', []);
            $interval = $request->get('interval', 'day');

            $kodeOwner = $this->getKodeOwner();

            $cacheKey = "device_trends_{$kodeOwner}_{$startDate}_{$endDate}_{$interval}_" . md5(json_encode($deviceTypes));

            $result = Cache::remember($cacheKey, 300, function () use ($kodeOwner, $startDate, $endDate, $deviceTypes, $interval) {

                $query = DB::table('sevices')
                    ->where('kode_owner', $kodeOwner)
                    ->where('status_services', 'Selesai')
                    ->whereBetween('updated_at', [
                        $startDate . ' 00:00:00',
                        $endDate . ' 23:59:59'
                    ]);

                // Filter by specific device types if provided
                if (!empty($deviceTypes)) {
                    $query->whereIn('type_unit', $deviceTypes);
                }

                // Group by time interval and device type
                $dateFormat = $this->getDateFormat($interval);

                $trends = $query
                    ->select([
                        DB::raw("DATE_FORMAT(updated_at, '{$dateFormat}') as period"),
                        'type_unit',
                        DB::raw('COUNT(*) as service_count'),
                        DB::raw('SUM(total_biaya) as revenue'),
                        DB::raw('AVG(total_biaya) as avg_revenue')
                    ])
                    ->groupBy('period', 'type_unit')
                    ->orderBy('period')
                    ->orderBy('revenue', 'desc')
                    ->get();

                // Transform data for easier frontend consumption
                $trendsByPeriod = [];
                foreach ($trends as $trend) {
                    if (!isset($trendsByPeriod[$trend->period])) {
                        $trendsByPeriod[$trend->period] = [
                            'period' => $trend->period,
                            'total_services' => 0,
                            'total_revenue' => 0,
                            'devices' => []
                        ];
                    }

                    $trendsByPeriod[$trend->period]['total_services'] += $trend->service_count;
                    $trendsByPeriod[$trend->period]['total_revenue'] += $trend->revenue;
                    $trendsByPeriod[$trend->period]['devices'][] = [
                        'type_unit' => $trend->type_unit,
                        'service_count' => $trend->service_count,
                        'revenue' => $trend->revenue,
                        'avg_revenue' => round($trend->avg_revenue, 2)
                    ];
                }

                return [
                    'trends_by_period' => array_values($trendsByPeriod),
                    'trends_raw' => $trends,
                    'metadata' => [
                        'interval' => $interval,
                        'device_types_filter' => $deviceTypes,
                        'total_periods' => count($trendsByPeriod)
                    ]
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Device trends retrieved successfully',
                'data' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Get Device Trends Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device trends',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    /**
     * Get device comparison report
     */
    public function getDeviceComparison(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'period1_start' => 'required|date',
                'period1_end' => 'required|date|after_or_equal:period1_start',
                'period2_start' => 'required|date',
                'period2_end' => 'required|date|after_or_equal:period2_start',
                'device_types' => 'sometimes|array'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $kodeOwner = $this->getKodeOwner();
            $deviceTypes = $request->get('device_types', []);

            // Get data for both periods
            $period1Data = $this->getDeviceDataForPeriod(
                $kodeOwner,
                $request->period1_start,
                $request->period1_end,
                $deviceTypes
            );

            $period2Data = $this->getDeviceDataForPeriod(
                $kodeOwner,
                $request->period2_start,
                $request->period2_end,
                $deviceTypes
            );

            // Calculate comparisons
            $comparison = $this->calculateDeviceComparison($period1Data, $period2Data);

            return response()->json([
                'success' => true,
                'message' => 'Device comparison retrieved successfully',
                'data' => [
                    'period1' => [
                        'date_range' => $request->period1_start . ' to ' . $request->period1_end,
                        'data' => $period1Data
                    ],
                    'period2' => [
                        'date_range' => $request->period2_start . ' to ' . $request->period2_end,
                        'data' => $period2Data
                    ],
                    'comparison' => $comparison
                ]
            ]);

        } catch (\Exception $e) {
            Log::error("Get Device Comparison Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving device comparison',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Helper methods

    private function getKodeOwner()
    {
        // Adjust this based on your authentication system
        return auth()->user()->userDetail->id_upline ?? auth()->user()->id;
    }

    private function getSortColumn($sortBy)
    {
        switch ($sortBy) {
            case 'count':
                return 'total_services';
            case 'revenue':
                return 'total_revenue';
            case 'avg_revenue':
                return 'avg_revenue';
            default:
                return 'total_revenue';
        }
    }

    private function getDateFormat($interval)
    {
        switch ($interval) {
            case 'day':
                return '%Y-%m-%d';
            case 'week':
                return '%Y-%u'; // Year-Week
            case 'month':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }

    private function getTimeSeriesData($query, $groupBy)
    {
        $dateFormat = $this->getDateFormat($groupBy);

        return $query
            ->select([
                DB::raw("DATE_FORMAT(updated_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as service_count'),
                DB::raw('SUM(total_biaya) as revenue'),
                DB::raw('COUNT(DISTINCT type_unit) as unique_devices')
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get();
    }

    private function getDeviceDataForPeriod($kodeOwner, $startDate, $endDate, $deviceTypes = [])
    {
        $query = DB::table('sevices')
            ->where('kode_owner', $kodeOwner)
            ->where('status_services', 'Selesai')
            ->whereBetween('updated_at', [
                $startDate . ' 00:00:00',
                $endDate . ' 23:59:59'
            ]);

        if (!empty($deviceTypes)) {
            $query->whereIn('type_unit', $deviceTypes);
        }

        return $query
            ->select([
                'type_unit',
                DB::raw('COUNT(*) as total_services'),
                DB::raw('SUM(total_biaya) as total_revenue'),
                DB::raw('AVG(total_biaya) as avg_revenue')
            ])
            ->groupBy('type_unit')
            ->orderByDesc('total_revenue')
            ->get();
    }

    private function calculateDeviceComparison($period1Data, $period2Data)
    {
        $comparison = [];

        // Create lookup for period2 data
        $period2Lookup = [];
        foreach ($period2Data as $item) {
            $period2Lookup[$item->type_unit] = $item;
        }

        // Calculate comparisons
        foreach ($period1Data as $item1) {
            $deviceType = $item1->type_unit;
            $item2 = $period2Lookup[$deviceType] ?? null;

            $comparison[$deviceType] = [
                'device_type' => $deviceType,
                'period1' => [
                    'services' => $item1->total_services,
                    'revenue' => $item1->total_revenue,
                    'avg_revenue' => round($item1->avg_revenue, 2)
                ],
                'period2' => [
                    'services' => $item2 ? $item2->total_services : 0,
                    'revenue' => $item2 ? $item2->total_revenue : 0,
                    'avg_revenue' => $item2 ? round($item2->avg_revenue, 2) : 0
                ]
            ];

            // Calculate percentage changes
            $comparison[$deviceType]['changes'] = [
                'services_change' => $this->calculatePercentageChange(
                    $item2 ? $item2->total_services : 0,
                    $item1->total_services
                ),
                'revenue_change' => $this->calculatePercentageChange(
                    $item2 ? $item2->total_revenue : 0,
                    $item1->total_revenue
                ),
                'avg_revenue_change' => $this->calculatePercentageChange(
                    $item2 ? $item2->avg_revenue : 0,
                    $item1->avg_revenue
                )
            ];
        }

        return array_values($comparison);
    }

    private function calculatePercentageChange($oldValue, $newValue)
    {
        if ($oldValue == 0) {
            return $newValue > 0 ? 100 : 0;
        }

        return round((($newValue - $oldValue) / $oldValue) * 100, 2);
    }
    //report service
}
