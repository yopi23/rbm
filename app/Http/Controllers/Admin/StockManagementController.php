<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockManagementController extends Controller
{
    /**
     * Menampilkan dashboard dengan overview stok dan penjualan
     */
    public function dashboard()
    {
        $page ='Statistik';
        // [
        //     'title' => 'Dashboard Inventory Management',
        //     'subtitle' => 'Statistik dan Analisis Stok Sparepart',
        //     'active_menu' => 'inventory-dashboard',
        // ];

        // Mendapatkan sparepart dengan stok di bawah threshold (misalnya 5)
        $lowStockItems = $this->getLowStockItems(5);

        // Mendapatkan produk terlaris (30 hari terakhir)
        $topSellingItems = $this->getTopSellingItems(30);

        // Statistik penjualan harian (7 hari terakhir)
        $dailySales = $this->getDailySales(7);

        // Generate view dengan menggunakan blank_page layout
        $content = view('admin.page.inventory.dashboard', compact(
            'lowStockItems',
            'topSellingItems',
            'dailySales'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan laporan restock
     */
    public function restockReport(Request $request)
    {
        $page ='Laporan Restock';
        // [
        //     'title' => 'Laporan Restock',
        //     'subtitle' => 'Daftar Sparepart yang Perlu Di-restock',
        //     'active_menu' => 'inventory-restock',
        // ];

        $threshold = $request->input('threshold', 10);

        $lowStockItems = $this->getLowStockItems($threshold);

        // Tambahkan informasi penjualan 30 hari terakhir untuk tiap item
        foreach ($lowStockItems as $item) {
            $item->sales_last_30_days = $this->getItemSales($item->id, 30);
            $item->estimated_days_left = $item->stok_sparepart > 0 && $item->sales_last_30_days > 0 ?
                round(($item->stok_sparepart / ($item->sales_last_30_days / 30)) * 30) : 0;
        }

        // Generate view dengan menggunakan blank_page layout
        $content = view('admin.page.inventory.restock_report', compact(
            'lowStockItems',
            'threshold'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan laporan produk terlaris
     */
    public function bestSellersReport(Request $request)
    {
        $page ='Produk Terlaris';
        // [
        //     'title' => 'Produk Terlaris',
        //     'subtitle' => 'Analisis Penjualan Sparepart',
        //     'active_menu' => 'inventory-bestsellers',
        // ];

        $days = $request->input('days', 30);
        $limit = $request->input('limit', 20);

        $topSellingItems = $this->getTopSellingItems($days, $limit);

        // Generate view dengan menggunakan blank_page layout
        $content = view('admin.page.inventory.bestsellers', compact(
            'topSellingItems',
            'days',
            'limit'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Mendapatkan daftar sparepart dengan stok di bawah threshold
     */
    public function getLowStockItems($threshold = 5)
    {
        return Sparepart::where('stok_sparepart', '<=', $threshold)
            ->orderBy('stok_sparepart', 'asc')
            ->get();
    }

    /**
     * Mendapatkan produk terlaris berdasarkan periode (dalam hari)
     */
    public function getTopSellingItems($days = 30, $limit = 10)
    {
        $startDate = Carbon::now()->subDays($days);

        // Gabungkan data penjualan dari dua tabel: detail_sparepart_penjualan dan detail_part_services
        $salesFromPenjualan = DetailSparepartPenjualan::select(
                'kode_sparepart',
                DB::raw('SUM(qty_sparepart) as total_qty')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('kode_sparepart');

        $salesFromServices = DetailPartServices::select(
                'kode_sparepart',
                DB::raw('SUM(qty_part) as total_qty')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('kode_sparepart');

        // Gabungkan kedua query
        $combinedSales = $salesFromPenjualan->unionAll($salesFromServices);

        // Dapatkan hasil akhir dengan sum dari seluruh penjualan
        $topProducts = DB::query()
            ->fromSub($combinedSales, 'combined_sales')
            ->select(
                'combined_sales.kode_sparepart',
                DB::raw('SUM(combined_sales.total_qty) as grand_total')
            )
            ->groupBy('combined_sales.kode_sparepart')
            ->orderBy('grand_total', 'desc')
            ->limit($limit)
            ->get();

        // Ambil data lengkap sparepart
        $result = [];
        foreach ($topProducts as $product) {
            $sparepart = Sparepart::find($product->kode_sparepart);
            if ($sparepart) {
                $sparepart->sold_qty = $product->grand_total;
                $result[] = $sparepart;
            }
        }

        return $result;
    }

    /**
     * Mendapatkan statistik penjualan harian
     */
    public function getDailySales($days = 7)
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dates[$dateKey] = [
                'date' => $dateKey,
                'sales_count' => 0,
                'service_count' => 0,
                'total_items' => 0,
            ];
            $currentDate->addDay();
        }

        // Hitung penjualan per hari
        $salesPerDay = DetailSparepartPenjualan::select(
                DB::raw('DATE(created_at) as sale_date'),
                DB::raw('COUNT(DISTINCT kode_penjualan) as sales_count'),
                DB::raw('SUM(qty_sparepart) as total_items')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('sale_date')
            ->get();

        foreach ($salesPerDay as $sale) {
            if (isset($dates[$sale->sale_date])) {
                $dates[$sale->sale_date]['sales_count'] = $sale->sales_count;
                $dates[$sale->sale_date]['total_items'] += $sale->total_items;
            }
        }

        // Hitung service per hari
        $servicesPerDay = DetailPartServices::select(
                DB::raw('DATE(created_at) as service_date'),
                DB::raw('COUNT(DISTINCT kode_services) as service_count'),
                DB::raw('SUM(qty_part) as total_items')
            )
            ->where('created_at', '>=', $startDate)
            ->groupBy('service_date')
            ->get();

        foreach ($servicesPerDay as $service) {
            if (isset($dates[$service->service_date])) {
                $dates[$service->service_date]['service_count'] = $service->service_count;
                $dates[$service->service_date]['total_items'] += $service->total_items;
            }
        }

        return array_values($dates);
    }

    /**
     * API untuk mendapatkan rekomendasi jumlah reorder untuk satu item
     */
    public function getReorderRecommendation(Request $request, $itemId)
    {
        $sparepart = Sparepart::findOrFail($itemId);

        // Parameter untuk perhitungan
        $leadTime = $request->input('lead_time', 7); // dalam hari
        $safetyStock = $request->input('safety_stock', 5); // minimal stok yang harus ada
        $periodSales = $this->getItemSales($itemId, 90); // penjualan 90 hari terakhir

        // Rata-rata penjualan harian
        $dailyAverage = $periodSales / 90;

        // Jumlah yang perlu dipesan = (Lead Time * Average Daily Sales) + Safety Stock - Current Stock
        $reorderQuantity = ceil(($leadTime * $dailyAverage) + $safetyStock - $sparepart->stok_sparepart);
        $reorderQuantity = max(0, $reorderQuantity); // Tidak boleh negatif

        return response()->json([
            'item' => $sparepart,
            'period_sales' => $periodSales,
            'daily_average' => round($dailyAverage, 2),
            'lead_time' => $leadTime,
            'safety_stock' => $safetyStock,
            'current_stock' => $sparepart->stok_sparepart,
            'reorder_quantity' => $reorderQuantity
        ]);
    }

    /**
     * Mendapatkan total penjualan untuk satu item dalam periode tertentu
     */
    private function getItemSales($itemId, $days)
    {
        $startDate = Carbon::now()->subDays($days);

        $salesFromPenjualan = DetailSparepartPenjualan::where('kode_sparepart', $itemId)
            ->where('created_at', '>=', $startDate)
            ->sum('qty_sparepart');

        $salesFromServices = DetailPartServices::where('kode_sparepart', $itemId)
            ->where('created_at', '>=', $startDate)
            ->sum('qty_part');

        return $salesFromPenjualan + $salesFromServices;
    }

    /**
     * API untuk mendapatkan data chart untuk grafik stok dan penjualan item tertentu
     */
    public function getItemStockAndSalesChart($itemId)
    {
        $sparepart = Sparepart::findOrFail($itemId);

        // Data 30 hari terakhir
        $endDate = Carbon::now();
        $startDate = Carbon::now()->subDays(30);

        // Format tanggal
        $dates = [];
        $currentDate = $startDate->copy();

        while ($currentDate <= $endDate) {
            $dateKey = $currentDate->format('Y-m-d');
            $dates[$dateKey] = [
                'date' => $dateKey,
                'sales' => 0,
                'service' => 0,
            ];
            $currentDate->addDay();
        }

        // Penjualan per hari
        $dailySales = DetailSparepartPenjualan::where('kode_sparepart', $itemId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(qty_sparepart) as total')
            )
            ->groupBy('date')
            ->get();

        foreach ($dailySales as $sale) {
            if (isset($dates[$sale->date])) {
                $dates[$sale->date]['sales'] = $sale->total;
            }
        }

        // Service per hari
        $dailyService = DetailPartServices::where('kode_sparepart', $itemId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(qty_part) as total')
            )
            ->groupBy('date')
            ->get();

        foreach ($dailyService as $service) {
            if (isset($dates[$service->date])) {
                $dates[$service->date]['service'] = $service->total;
            }
        }

        // Konversi ke format untuk chart
        $chartData = array_values($dates);

        return response()->json([
            'item' => $sparepart,
            'chart_data' => $chartData
        ]);
    }
}
