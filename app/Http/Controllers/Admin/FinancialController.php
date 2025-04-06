<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Financial;
use App\Models\FinancialCategory;
use App\Models\UserDetail;
use App\Models\Sevices;
use App\Models\Pengambilan;
use App\Models\Penjualan;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailSparepartPenjualan;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\HistoryLaci;
use App\Traits\KategoriLaciTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\FinancialExport;

class FinancialController extends Controller
{
    use KategoriLaciTrait;

    public function getThisUser()
    {
        $user = Auth::user();
        $detail = UserDetail::where('kode_user', $user->id)->first();
        return $detail;
    }

    // Halaman dashboard keuangan
    public function index(Request $request)
    {
        $page = "Manajemen Keuangan";
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', date('m'));

        // Sinkronisasi data keuangan dari tabel lain
        $this->syncFinancialData();

        // Data untuk chart bulanan
        $monthlyData = $this->getMonthlyData($year);

        // Data statistik pendapatan dan pengeluaran
        $stats = $this->getFinancialStats($year, $month);

        // Data kategori untuk form
        $categories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                      ->where('is_active', true)
                                      ->get();

        // Transaksi terbaru (5)
        $latestTransactions = Financial::with('user')
                                     ->where('kode_owner', $this->getThisUser()->id_upline)
                                     ->orderBy('tanggal', 'desc')
                                     ->orderBy('created_at', 'desc')
                                     ->limit(5)
                                     ->get();

        // Daftar Kategori Laci untuk filter
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.dashboard', compact(
            'page',
            'monthlyData',
            'stats',
            'categories',
            'latestTransactions',
            'year',
            'month',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Sinkronisasi data keuangan dari tabel lain
    private function syncFinancialData()
    {
        $kodeOwner = $this->getThisUser()->id_upline;

        // 1. Sinkronisasi data DP Service
        $this->syncServiceDeposits($kodeOwner);

        // 2. Sinkronisasi data Pengambilan Service
        $this->syncServicePickups($kodeOwner);

        // 3. Sinkronisasi data Penjualan
        $this->syncSalesData($kodeOwner);

        // 4. Sinkronisasi data Pengeluaran Operasional
        $this->syncOperationalExpenses($kodeOwner);

        // 5. Sinkronisasi data Pengeluaran Toko
        $this->syncStoreExpenses($kodeOwner);

        // 6. Sinkronisasi data History Laci
        $this->syncCashDrawerHistory($kodeOwner);
    }

    // Sinkronisasi data DP Service
    private function syncServiceDeposits($kodeOwner)
    {
        // Ambil semua service yang memiliki DP dan belum direkam di financial
        $services = Sevices::where('kode_owner', $kodeOwner)
                        ->where('dp', '>', 0)
                        ->whereNotIn('id', function($query) {
                            $query->select('kode_referensi')
                                  ->from('financials')
                                  ->where('kategori', 'DP Service')
                                  ->whereNotNull('kode_referensi');
                        })
                        ->get();

        foreach ($services as $service) {
            // Buat transaksi financial baru untuk DP Service
            Financial::create([
                'tanggal' => $service->tgl_service,
                'kode_transaksi' => 'FIN-DP-' . $service->kode_service,
                'tipe_transaksi' => 'Pemasukan',
                'kategori' => 'DP Service',
                'deskripsi' => 'DP Service untuk ' . $service->nama_pelanggan . ' - ' . $service->type_unit,
                'jumlah' => $service->dp,
                'metode_pembayaran' => 'Cash', // Default, bisa ditambahkan kolom di Sevices jika perlu
                'kode_referensi' => $service->id,
                'kode_owner' => $kodeOwner,
                'user_input' => $service->id_teknisi ?? Auth::id(),
            ]);
        }
    }

    // Sinkronisasi data Pengambilan Service
    private function syncServicePickups($kodeOwner)
    {
        // Ambil semua pengambilan service yang sudah selesai (status_pengambilan = 1)
        $pickups = Pengambilan::where('kode_owner', $kodeOwner)
                          ->where('status_pengambilan', '1')
                          ->whereNotIn('id', function($query) {
                              $query->select('kode_referensi')
                                    ->from('financials')
                                    ->where('kategori', 'Pengambilan Service')
                                    ->whereNotNull('kode_referensi');
                          })
                          ->get();

        foreach ($pickups as $pickup) {
            // Hitung jumlah yang harus dibayar (total_bayar dikurangi DP)
            $services = Sevices::where('kode_pengambilan', $pickup->id)->get();
            $totalService = 0;
            $serviceDesc = [];

            foreach ($services as $service) {
                $totalService += $service->total_biaya - $service->dp;
                $serviceDesc[] = $service->kode_service . ' (' . $service->type_unit . ')';
            }

            // Buat transaksi financial baru untuk Pengambilan Service
            if ($totalService > 0) {
                Financial::create([
                    'tanggal' => $pickup->tgl_pengambilan,
                    'kode_transaksi' => 'FIN-PU-' . $pickup->kode_pengambilan,
                    'tipe_transaksi' => 'Pemasukan',
                    'kategori' => 'Pengambilan Service',
                    'deskripsi' => 'Pelunasan Service oleh ' . $pickup->nama_pengambilan . ' untuk ' . implode(', ', $serviceDesc),
                    'jumlah' => $totalService,
                    'metode_pembayaran' => 'Cash', // Default, bisa ditambahkan kolom jika perlu
                    'kode_referensi' => $pickup->id,
                    'kode_owner' => $kodeOwner,
                    'user_input' => $pickup->user_input,
                ]);
            }
        }
    }

    // Sinkronisasi data Penjualan
    private function syncSalesData($kodeOwner)
    {
        // Ambil semua penjualan yang sudah selesai (status_penjualan = 1)
        $sales = Penjualan::where('kode_owner', $kodeOwner)
                      ->where('status_penjualan', '1')
                      ->whereNotIn('id', function($query) {
                          $query->select('kode_referensi')
                                ->from('financials')
                                ->where('kategori', 'Penjualan')
                                ->whereNotNull('kode_referensi');
                      })
                      ->get();

        foreach ($sales as $sale) {
            // Dapatkan detail penjualan
            $barangDetail = DetailBarangPenjualan::where('kode_penjualan', $sale->id)->get();
            $sparepartDetail = DetailSparepartPenjualan::where('kode_penjualan', $sale->id)->get();

            $barangDesc = $barangDetail->count() > 0 ? $barangDetail->count() . ' unit barang' : '';
            $sparepartDesc = $sparepartDetail->count() > 0 ? $sparepartDetail->count() . ' jenis sparepart' : '';

            $description = 'Penjualan kepada ' . $sale->nama_customer;
            if (!empty($barangDesc) || !empty($sparepartDesc)) {
                $description .= ' (' . implode(', ', array_filter([$barangDesc, $sparepartDesc])) . ')';
            }

            // Buat transaksi financial baru untuk Penjualan
            Financial::create([
                'tanggal' => $sale->tgl_penjualan ?? $sale->created_at,
                'kode_transaksi' => 'FIN-SALE-' . $sale->kode_penjualan,
                'tipe_transaksi' => 'Pemasukan',
                'kategori' => 'Penjualan',
                'deskripsi' => $description,
                'jumlah' => $sale->total_penjualan,
                'metode_pembayaran' => 'Cash', // Default, bisa ditambahkan kolom jika perlu
                'kode_referensi' => $sale->id,
                'kode_owner' => $kodeOwner,
                'user_input' => $sale->user_input,
            ]);
        }
    }

    // Sinkronisasi data Pengeluaran Operasional
    private function syncOperationalExpenses($kodeOwner)
    {
        // Ambil semua pengeluaran operasional yang belum direkam di financial
        $expenses = PengeluaranOperasional::where('kode_owner', $kodeOwner)
                                      ->whereNotIn('id', function($query) {
                                          $query->select('kode_referensi')
                                                ->from('financials')
                                                ->where('kategori', 'LIKE', 'Operasional:%')
                                                ->whereNotNull('kode_referensi');
                                      })
                                      ->get();

        foreach ($expenses as $expense) {
            // Kategori detail untuk pengeluaran operasional
            $kategori = 'Operasional:' . $expense->kategori;

            // Deskripsi tambahan untuk penggajian
            $deskripsi = $expense->nama_pengeluaran;
            if ($expense->kategori == 'Penggajian' && $expense->kode_pegawai != '-') {
                $pegawai = \App\Models\User::find($expense->kode_pegawai);
                if ($pegawai) {
                    $deskripsi .= ' untuk ' . $pegawai->name;
                }
            }

            // Buat transaksi financial baru untuk Pengeluaran Operasional
            Financial::create([
                'tanggal' => $expense->tgl_pengeluaran,
                'kode_transaksi' => 'FIN-OPEX-' . $expense->id,
                'tipe_transaksi' => 'Pengeluaran',
                'kategori' => $kategori,
                'deskripsi' => $deskripsi . ($expense->desc_pengeluaran ? ' - ' . $expense->desc_pengeluaran : ''),
                'jumlah' => $expense->jml_pengeluaran,
                'metode_pembayaran' => 'Cash', // Default
                'kode_referensi' => $expense->id,
                'kode_owner' => $kodeOwner,
                'user_input' => Auth::id(), // User saat ini, karena mungkin tidak ada di tabel asal
            ]);
        }
    }

    // Sinkronisasi data Pengeluaran Toko
    private function syncStoreExpenses($kodeOwner)
    {
        // Ambil semua pengeluaran toko yang belum direkam di financial
        $expenses = PengeluaranToko::where('kode_owner', $kodeOwner)
                                ->whereNotIn('id', function($query) {
                                    $query->select('kode_referensi')
                                          ->from('financials')
                                          ->where('kategori', 'Pengeluaran Toko')
                                          ->whereNotNull('kode_referensi');
                                })
                                ->get();

        foreach ($expenses as $expense) {
            // Buat transaksi financial baru untuk Pengeluaran Toko
            Financial::create([
                'tanggal' => $expense->tanggal_pengeluaran,
                'kode_transaksi' => 'FIN-STORE-' . $expense->id,
                'tipe_transaksi' => 'Pengeluaran',
                'kategori' => 'Pengeluaran Toko',
                'deskripsi' => $expense->nama_pengeluaran . ($expense->catatan_pengeluaran ? ' - ' . $expense->catatan_pengeluaran : ''),
                'jumlah' => $expense->jumlah_pengeluaran,
                'metode_pembayaran' => 'Cash', // Default
                'kode_referensi' => $expense->id,
                'kode_owner' => $kodeOwner,
                'user_input' => Auth::id(), // User saat ini, karena mungkin tidak ada di tabel asal
            ]);
        }
    }

    // Sinkronisasi data History Laci
    private function syncCashDrawerHistory($kodeOwner)
    {
        // Ambil semua history laci yang belum direkam di financial
        $histories = HistoryLaci::where('kode_owner', $kodeOwner)
                               ->whereNotIn('id', function($query) {
                                   $query->select('kode_referensi')
                                         ->from('financials')
                                         ->where('kategori', 'LIKE', 'Laci:%')
                                         ->whereNotNull('kode_referensi');
                               })
                               ->get();

        foreach ($histories as $history) {
            // Ambil nama kategori laci
            $kategoriLaci = \App\Models\KategoriLaci::find($history->id_kategori);
            $namaKategori = $kategoriLaci ? $kategoriLaci->nama_kategori : 'Umum';

            // Tentukan tipe transaksi
            $tipeTransaksi = 'Pengeluaran';
            $jumlah = $history->keluar;

            if ($history->masuk > 0) {
                $tipeTransaksi = 'Pemasukan';
                $jumlah = $history->masuk;
            }

            // Jika tidak ada jumlah, lanjutkan ke record berikutnya
            if ($jumlah <= 0) {
                continue;
            }

            // Buat transaksi financial baru untuk History Laci
            Financial::create([
                'tanggal' => $history->created_at,
                'kode_transaksi' => 'FIN-LACI-' . $history->id,
                'tipe_transaksi' => $tipeTransaksi,
                'kategori' => 'Laci:' . $namaKategori,
                'deskripsi' => $history->keterangan ?? 'Transaksi laci',
                'jumlah' => $jumlah,
                'metode_pembayaran' => 'Cash',
                'kode_referensi' => $history->id,
                'kode_owner' => $kodeOwner,
                'user_input' => Auth::id(),
            ]);
        }
    }

    // Mengambil data statistik keuangan
    private function getFinancialStats($year, $month)
    {
        $kodeOwner = $this->getThisUser()->id_upline;

        // Filter data berdasarkan tahun dan bulan
        $query = Financial::where('kode_owner', $kodeOwner);

        if ($month) {
            $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
            $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();
        } else {
            $startDate = Carbon::createFromDate($year, 1, 1)->startOfYear();
            $endDate = Carbon::createFromDate($year, 12, 31)->endOfYear();
        }

        $query->whereBetween('tanggal', [$startDate, $endDate]);

        // Statistik dasar
        $totalIncome = clone $query;
        $totalIncome = $totalIncome->where('tipe_transaksi', 'Pemasukan')->sum('jumlah');

        $totalExpense = clone $query;
        $totalExpense = $totalExpense->where('tipe_transaksi', 'Pengeluaran')->sum('jumlah');

        $netProfit = $totalIncome - $totalExpense;

        // Top kategori pemasukan
        $topIncomeCategories = clone $query;
        $topIncomeCategories = $topIncomeCategories->select('kategori', DB::raw('SUM(jumlah) as total'))
                                                ->where('tipe_transaksi', 'Pemasukan')
                                                ->groupBy('kategori')
                                                ->orderBy('total', 'desc')
                                                ->limit(5)
                                                ->get();

        // Top kategori pengeluaran
        $topExpenseCategories = clone $query;
        $topExpenseCategories = $topExpenseCategories->select('kategori', DB::raw('SUM(jumlah) as total'))
                                                  ->where('tipe_transaksi', 'Pengeluaran')
                                                  ->groupBy('kategori')
                                                  ->orderBy('total', 'desc')
                                                  ->limit(5)
                                                  ->get();

        // Pendapatan dari Service
        $serviceIncome = clone $query;
        $serviceIncome = $serviceIncome->where('tipe_transaksi', 'Pemasukan')
                                     ->where(function($q) {
                                         $q->where('kategori', 'DP Service')
                                           ->orWhere('kategori', 'Pengambilan Service');
                                     })
                                     ->sum('jumlah');

        // Pendapatan dari Penjualan
        $salesIncome = clone $query;
        $salesIncome = $salesIncome->where('tipe_transaksi', 'Pemasukan')
                                 ->where('kategori', 'Penjualan')
                                 ->sum('jumlah');

        // Pengeluaran untuk Operasional
        $operationalExpense = clone $query;
        $operationalExpense = $operationalExpense->where('tipe_transaksi', 'Pengeluaran')
                                               ->where('kategori', 'LIKE', 'Operasional:%')
                                               ->sum('jumlah');

        // Pengeluaran untuk Toko
        $storeExpense = clone $query;
        $storeExpense = $storeExpense->where('tipe_transaksi', 'Pengeluaran')
                                   ->where('kategori', 'Pengeluaran Toko')
                                   ->sum('jumlah');

        // Return semua statistik
        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
            'topIncomeCategories' => $topIncomeCategories,
            'topExpenseCategories' => $topExpenseCategories,
            'serviceIncome' => $serviceIncome,
            'salesIncome' => $salesIncome,
            'operationalExpense' => $operationalExpense,
            'storeExpense' => $storeExpense,
        ];
    }

    // Mengambil data bulanan untuk chart
    private function getMonthlyData($year)
    {
        $kodeOwner = $this->getThisUser()->id_upline;

        $incomeData = DB::table('financials')
                      ->select(DB::raw('MONTH(tanggal) as month'), DB::raw('SUM(jumlah) as total'))
                      ->where('kode_owner', $kodeOwner)
                      ->where('tipe_transaksi', 'Pemasukan')
                      ->whereYear('tanggal', $year)
                      ->groupBy(DB::raw('MONTH(tanggal)'))
                      ->get()
                      ->pluck('total', 'month')
                      ->toArray();

        $expenseData = DB::table('financials')
                       ->select(DB::raw('MONTH(tanggal) as month'), DB::raw('SUM(jumlah) as total'))
                       ->where('kode_owner', $kodeOwner)
                       ->where('tipe_transaksi', 'Pengeluaran')
                       ->whereYear('tanggal', $year)
                       ->groupBy(DB::raw('MONTH(tanggal)'))
                       ->get()
                       ->pluck('total', 'month')
                       ->toArray();

        $months = [
            1 => 'Jan', 2 => 'Feb', 3 => 'Mar', 4 => 'Apr',
            5 => 'Mei', 6 => 'Jun', 7 => 'Jul', 8 => 'Ags',
            9 => 'Sep', 10 => 'Okt', 11 => 'Nov', 12 => 'Des'
        ];

        $chartData = [
            'labels' => [],
            'income' => [],
            'expense' => [],
            'profit' => []
        ];

        foreach ($months as $month => $label) {
            $income = isset($incomeData[$month]) ? $incomeData[$month] : 0;
            $expense = isset($expenseData[$month]) ? $expenseData[$month] : 0;
            $profit = $income - $expense;

            $chartData['labels'][] = $label;
            $chartData['income'][] = $income;
            $chartData['expense'][] = $expense;
            $chartData['profit'][] = $profit;
        }

        return $chartData;
    }

    // List semua transaksi keuangan
    public function transactions(Request $request)
    {
        $page = "Daftar Transaksi Keuangan";

        // Ensure financials are synced
        $this->syncFinancialData();

        // Filter
        $year = $request->input('year', date('Y'));
        $month = $request->input('month', null);
        $type = $request->input('type', null);
        $category = $request->input('category', null);
        $source = $request->input('source', null); // Tambahkan filter sumber data

        // Query dasar
        $query = Financial::with('user')
                       ->where('kode_owner', $this->getThisUser()->id_upline);

        // Filter berdasarkan tahun dan bulan
        if ($month) {
            $query->whereYear('tanggal', $year)
                  ->whereMonth('tanggal', $month);
        } else {
            $query->whereYear('tanggal', $year);
        }

        // Filter berdasarkan tipe transaksi
        if ($type) {
            $query->where('tipe_transaksi', $type);
        }

        // Filter berdasarkan kategori
        if ($category) {
            $query->where('kategori', $category);
        }

        // Filter berdasarkan sumber data
        if ($source) {
            switch ($source) {
                case 'service':
                    $query->where(function($q) {
                        $q->where('kategori', 'DP Service')
                          ->orWhere('kategori', 'Pengambilan Service');
                    });
                    break;
                case 'sales':
                    $query->where('kategori', 'Penjualan');
                    break;
                case 'operational':
                    $query->where('kategori', 'LIKE', 'Operasional:%');
                    break;
                case 'store':
                    $query->where('kategori', 'Pengeluaran Toko');
                    break;
                case 'laci':
                    $query->where('kategori', 'LIKE', 'Laci:%');
                    break;
                case 'manual':
                    $query->whereNull('kode_referensi');
                    break;
            }
        }

        // Execute query with pagination
        $transactions = $query->orderBy('tanggal', 'desc')
                            ->orderBy('created_at', 'desc')
                            ->paginate(15);

        // Data untuk filter dropdown
        $categories = DB::table('financials')
                      ->select('kategori')
                      ->where('kode_owner', $this->getThisUser()->id_upline)
                      ->groupBy('kategori')
                      ->orderBy('kategori')
                      ->get()
                      ->pluck('kategori');

        $years = Financial::select(DB::raw('YEAR(tanggal) as year'))
                       ->where('kode_owner', $this->getThisUser()->id_upline)
                       ->groupBy(DB::raw('YEAR(tanggal)'))
                       ->orderBy('year', 'desc')
                       ->pluck('year')
                       ->toArray();

        // Tambahkan tahun saat ini jika belum ada dalam data
        if (!in_array(date('Y'), $years)) {
            $years[] = date('Y');
            sort($years);
        }

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.transactions', compact(
            'page',
            'transactions',
            'categories',
            'years',
            'year',
            'month',
            'type',
            'category',
            'source',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Form tambah transaksi
    public function create()
    {
        $page = "Tambah Transaksi Keuangan";

        // Get kategori untuk dropdown
        $incomeCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                          ->where('tipe_kategori', 'Pemasukan')
                                          ->where('is_active', true)
                                          ->orderBy('nama_kategori')
                                          ->get();

        $expenseCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                           ->where('tipe_kategori', 'Pengeluaran')
                                           ->where('is_active', true)
                                           ->orderBy('nama_kategori')
                                           ->get();

        // Get service untuk referensi dropdown
        $services = Sevices::where('kode_owner', $this->getThisUser()->id_upline)
                        ->where('status_services', 'Selesai')
                        ->orderBy('id', 'desc')
                        ->get();

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.create', compact(
            'page',
            'incomeCategories',
            'expenseCategories',
            'services',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Store transaksi baru
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tipe_transaksi' => 'required|in:Pemasukan,Pengeluaran',
            'kategori' => 'required',
            'jumlah' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required',
            'deskripsi' => 'nullable|string',
            'kode_referensi' => 'nullable',
            'id_kategorilaci' => 'required_if:update_laci,1', // Validasi kategori laci jika update_laci = 1
        ]);

        // Generate kode transaksi (FIN-YYYYMMDD-XXX)
        $today = Carbon::now()->format('Ymd');
        $count = Financial::whereDate('created_at', Carbon::today())->count() + 1;
        $kodeTransaksi = 'FIN-' . $today . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);

        DB::beginTransaction();
        try {
            // Simpan transaksi
            $financial = Financial::create([
                'tanggal' => $request->tanggal,
                'kode_transaksi' => $kodeTransaksi,
                'tipe_transaksi' => $request->tipe_transaksi,
                'kategori' => $request->kategori,
                'deskripsi' => $request->deskripsi,
                'jumlah' => $request->jumlah,
                'metode_pembayaran' => $request->metode_pembayaran,
                'kode_referensi' => $request->kode_referensi,
                'kode_owner' => $this->getThisUser()->id_upline,
                'user_input' => Auth::id(),
            ]);

            // Update laci jika diperlukan
            if ($request->has('update_laci') && $request->update_laci == 1) {
                $uangMasuk = $request->tipe_transaksi == 'Pemasukan' ? $request->jumlah : null;
                $uangKeluar = $request->tipe_transaksi == 'Pengeluaran' ? $request->jumlah : null;

                // Catat histori laci
                $this->recordLaciHistory(
                    $request->id_kategorilaci,
                    $uangMasuk,
                    $uangKeluar,
                    $request->deskripsi ?? 'Transaksi dari manajemen keuangan'
                );
            }

            DB::commit();
            return redirect()->route('financial.index')->with('success', 'Transaksi berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    // Edit transaksi
    public function edit($id)
    {
        $page = "Edit Transaksi Keuangan";

        $transaction = Financial::findOrFail($id);

        // Cek apakah transaksi milik owner yang sama
        if ($transaction->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.index')->with('error', 'Anda tidak memiliki akses ke transaksi ini');
        }

        // Get kategori untuk dropdown
        $incomeCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                          ->where('tipe_kategori', 'Pemasukan')
                                          ->where('is_active', true)
                                          ->orderBy('nama_kategori')
                                          ->get();

        $expenseCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                           ->where('tipe_kategori', 'Pengeluaran')
                                           ->where('is_active', true)
                                           ->orderBy('nama_kategori')
                                           ->get();

        // Get service untuk referensi dropdown
        $services = Sevices::where('kode_owner', $this->getThisUser()->id_upline)
                        ->where('status_services', 'Selesai')
                        ->orderBy('id', 'desc')
                        ->get();

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        // Cek apakah ini transaksi auto-generated dari tabel lain
        $isAutoGenerated = !is_null($transaction->kode_referensi);
        $sourceTable = $this->getSourceTable($transaction->kategori);

        $content = view('admin.page.financial.edit', compact(
            'page',
            'transaction',
            'incomeCategories',
            'expenseCategories',
            'services',
            'isAutoGenerated',
            'sourceTable',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Mendapatkan tabel sumber berdasarkan kategori
    private function getSourceTable($kategori)
    {
        if (strpos($kategori, 'DP Service') === 0 || strpos($kategori, 'Pengambilan Service') === 0) {
            return 'Service';
        } elseif (strpos($kategori, 'Penjualan') === 0) {
            return 'Penjualan';
        } elseif (strpos($kategori, 'Operasional:') === 0) {
            return 'Pengeluaran Operasional';
        } elseif (strpos($kategori, 'Pengeluaran Toko') === 0) {
            return 'Pengeluaran Toko';
        } elseif (strpos($kategori, 'Laci:') === 0) {
            return 'History Laci';
        }

        return 'Manual';
    }

    // Update transaksi
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tipe_transaksi' => 'required|in:Pemasukan,Pengeluaran',
            'kategori' => 'required',
            'jumlah' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required',
            'deskripsi' => 'nullable|string',
            'kode_referensi' => 'nullable',
            'id_kategorilaci' => 'required_if:update_laci,1', // Validasi kategori laci jika update_laci = 1
        ]);

        $transaction = Financial::findOrFail($id);

        // Cek apakah transaksi milik owner yang sama
        if ($transaction->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.index')->with('error', 'Anda tidak memiliki akses ke transaksi ini');
        }

        // Khusus untuk transaksi yang di-generate otomatis, hanya izinkan update tertentu
        if (!is_null($transaction->kode_referensi)) {
            // Untuk transaksi auto-generated, hanya izinkan update deskripsi dan metode pembayaran
            $transaction->update([
                'deskripsi' => $request->deskripsi,
                'metode_pembayaran' => $request->metode_pembayaran,
            ]);
        } else {
            // Update transaksi manual
            $transaction->update([
                'tanggal' => $request->tanggal,
                'tipe_transaksi' => $request->tipe_transaksi,
                'kategori' => $request->kategori,
                'deskripsi' => $request->deskripsi,
                'jumlah' => $request->jumlah,
                'metode_pembayaran' => $request->metode_pembayaran,
                'kode_referensi' => $request->kode_referensi,
            ]);
        }

        // Update laci jika diperlukan dan transaksi manual
        if (is_null($transaction->kode_referensi) && $request->has('update_laci') && $request->update_laci == 1) {
            $uangMasuk = $request->tipe_transaksi == 'Pemasukan' ? $request->jumlah : null;
            $uangKeluar = $request->tipe_transaksi == 'Pengeluaran' ? $request->jumlah : null;

            // Catat histori laci
            $this->recordLaciHistory(
                $request->id_kategorilaci,
                $uangMasuk,
                $uangKeluar,
                $request->deskripsi ?? 'Update transaksi dari manajemen keuangan'
            );
        }

        return redirect()->route('financial.transactions')->with('success', 'Transaksi berhasil diperbarui');
    }

    // Hapus transaksi
    public function destroy($id)
    {
        $transaction = Financial::findOrFail($id);

        // Cek apakah transaksi milik owner yang sama
        if ($transaction->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.index')->with('error', 'Anda tidak memiliki akses ke transaksi ini');
        }

        // Untuk transaksi auto-generated, jangan izinkan penghapusan
        if (!is_null($transaction->kode_referensi)) {
            return redirect()->route('financial.transactions')->with('error', 'Transaksi yang dibuat otomatis tidak dapat dihapus. Silakan edit sumber data aslinya.');
        }

        $transaction->delete();

        return redirect()->route('financial.transactions')->with('success', 'Transaksi berhasil dihapus');
    }

    // Halaman manajemen kategori
    public function categories()
    {
        $page = "Kategori Keuangan";

        $categories = FinancialCategory::with('createdBy')
                                     ->where('kode_owner', $this->getThisUser()->id_upline)
                                     ->orderBy('tipe_kategori')
                                     ->orderBy('nama_kategori')
                                     ->get();

        $content = view('admin.page.financial.categories', compact('page', 'categories'));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Store kategori baru
    public function storeCategory(Request $request)
    {
        $request->validate([
            'nama_kategori' => 'required|string|max:255',
            'tipe_kategori' => 'required|in:Pemasukan,Pengeluaran',
        ]);

        // Cek jika kategori dengan nama yang sama sudah ada
        $exists = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                ->where('nama_kategori', $request->nama_kategori)
                                ->where('tipe_kategori', $request->tipe_kategori)
                                ->exists();

        if ($exists) {
            return back()->with('error', 'Kategori dengan nama tersebut sudah ada');
        }

        // Buat kategori baru
        FinancialCategory::create([
            'nama_kategori' => $request->nama_kategori,
            'tipe_kategori' => $request->tipe_kategori,
            'kode_owner' => $this->getThisUser()->id_upline,
            'created_by' => Auth::id(),
            'is_active' => true,
        ]);

        return redirect()->route('financial.categories')->with('success', 'Kategori berhasil ditambahkan');
    }

    // Update status kategori (aktif/non-aktif)
    public function updateCategoryStatus(Request $request, $id)
    {
        $category = FinancialCategory::findOrFail($id);

        // Cek apakah kategori milik owner yang sama
        if ($category->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.categories')->with('error', 'Anda tidak memiliki akses ke kategori ini');
        }

        $category->update([
            'is_active' => $request->is_active == 1,
        ]);

        return redirect()->route('financial.categories')->with('success', 'Status kategori berhasil diperbarui');
    }

    // Halaman laporan keuangan
    public function reports(Request $request)
    {
        $page = "Laporan Keuangan";

        // Ensure financials are synced
        $this->syncFinancialData();

        // Filter
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $reportType = $request->input('report_type', 'summary');
        $source = $request->input('source', null); // Filter sumber data

        // Query dasar
        $query = Financial::where('kode_owner', $this->getThisUser()->id_upline)
                       ->whereBetween('tanggal', [$startDate, $endDate]);

        // Filter berdasarkan sumber data
        if ($source) {
            switch ($source) {
                case 'service':
                    $query->where(function($q) {
                        $q->where('kategori', 'DP Service')
                          ->orWhere('kategori', 'Pengambilan Service');
                    });
                    break;
                case 'sales':
                    $query->where('kategori', 'Penjualan');
                    break;
                case 'operational':
                    $query->where('kategori', 'LIKE', 'Operasional:%');
                    break;
                case 'store':
                    $query->where('kategori', 'Pengeluaran Toko');
                    break;
                case 'laci':
                    $query->where('kategori', 'LIKE', 'Laci:%');
                    break;
                case 'manual':
                    $query->whereNull('kode_referensi');
                    break;
            }
        }

        // Jika generate report
        if ($request->has('generate')) {
            switch ($reportType) {
                case 'summary':
                    $report = $this->generateSummaryReport($query, $startDate, $endDate);
                    break;

                case 'detail':
                    $report = $this->generateDetailReport($query, $startDate, $endDate);
                    break;

                case 'category':
                    $report = $this->generateCategoryReport($query, $startDate, $endDate);
                    break;

                default:
                    $report = $this->generateSummaryReport($query, $startDate, $endDate);
            }

            $content = view('admin.page.financial.reports', compact(
                'page',
                'startDate',
                'endDate',
                'reportType',
                'source',
                'report'
            ));
            // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
        }

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.reports', compact(
            'page',
            'startDate',
            'endDate',
            'reportType',
            'source',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Generate laporan ringkasan
    private function generateSummaryReport($query, $startDate, $endDate)
    {
        $totalIncome = clone $query;
        $totalIncome = $totalIncome->where('tipe_transaksi', 'Pemasukan')->sum('jumlah');

        $totalExpense = clone $query;
        $totalExpense = $totalExpense->where('tipe_transaksi', 'Pengeluaran')->sum('jumlah');

        $netProfit = $totalIncome - $totalExpense;

        // Pemasukan per kategori
        $incomeByCategory = clone $query;
        $incomeByCategory = $incomeByCategory->select('kategori', DB::raw('SUM(jumlah) as total'))
                                          ->where('tipe_transaksi', 'Pemasukan')
                                          ->groupBy('kategori')
                                          ->orderBy('total', 'desc')
                                          ->get();

        // Pengeluaran per kategori
        $expenseByCategory = clone $query;
        $expenseByCategory = $expenseByCategory->select('kategori', DB::raw('SUM(jumlah) as total'))
                                           ->where('tipe_transaksi', 'Pengeluaran')
                                           ->groupBy('kategori')
                                           ->orderBy('total', 'desc')
                                           ->get();

        // Pemasukan dan pengeluaran per hari dalam periode
        $dailyTransactions = clone $query;
        $dailyTransactions = $dailyTransactions->select(
                                            'tanggal',
                                            DB::raw('SUM(CASE WHEN tipe_transaksi = "Pemasukan" THEN jumlah ELSE 0 END) as total_income'),
                                            DB::raw('SUM(CASE WHEN tipe_transaksi = "Pengeluaran" THEN jumlah ELSE 0 END) as total_expense')
                                        )
                                        ->groupBy('tanggal')
                                        ->orderBy('tanggal')
                                        ->get();

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'dailyTransactions' => $dailyTransactions,
        ];
    }

    // Generate laporan detail
    private function generateDetailReport($query, $startDate, $endDate)
    {
        // Semua transaksi dalam periode
        $transactions = clone $query;
        $transactions = $transactions->with('user')
                                  ->orderBy('tanggal')
                                  ->orderBy('created_at')
                                  ->get();

        // Ringkasan
        $totalIncome = clone $query;
        $totalIncome = $totalIncome->where('tipe_transaksi', 'Pemasukan')->sum('jumlah');

        $totalExpense = clone $query;
        $totalExpense = $totalExpense->where('tipe_transaksi', 'Pengeluaran')->sum('jumlah');

        $netProfit = $totalIncome - $totalExpense;

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'transactions' => $transactions,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ];
    }

    // Generate laporan per kategori
    private function generateCategoryReport($query, $startDate, $endDate)
    {
        // Pemasukan per kategori
        $incomeByCategory = clone $query;
        $incomeByCategory = $incomeByCategory->select('kategori', DB::raw('SUM(jumlah) as total'), DB::raw('COUNT(*) as count'))
                                          ->where('tipe_transaksi', 'Pemasukan')
                                          ->groupBy('kategori')
                                          ->orderBy('total', 'desc')
                                          ->get();

        // Pengeluaran per kategori
        $expenseByCategory = clone $query;
        $expenseByCategory = $expenseByCategory->select('kategori', DB::raw('SUM(jumlah) as total'), DB::raw('COUNT(*) as count'))
                                           ->where('tipe_transaksi', 'Pengeluaran')
                                           ->groupBy('kategori')
                                           ->orderBy('total', 'desc')
                                           ->get();

        // Ringkasan
        $totalIncome = clone $query;
        $totalIncome = $totalIncome->where('tipe_transaksi', 'Pemasukan')->sum('jumlah');

        $totalExpense = clone $query;
        $totalExpense = $totalExpense->where('tipe_transaksi', 'Pengeluaran')->sum('jumlah');

        $netProfit = $totalIncome - $totalExpense;

        return [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'incomeByCategory' => $incomeByCategory,
            'expenseByCategory' => $expenseByCategory,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $netProfit,
        ];
    }

    // Export laporan ke PDF
    public function exportPdf(Request $request)
    {
        // Filter
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $reportType = $request->input('report_type', 'summary');
        $source = $request->input('source');

        // Query dasar
        $query = Financial::where('kode_owner', $this->getThisUser()->id_upline)
                       ->whereBetween('tanggal', [$startDate, $endDate]);

        // Filter berdasarkan sumber data
        if ($source) {
            switch ($source) {
                case 'service':
                    $query->where(function($q) {
                        $q->where('kategori', 'DP Service')
                          ->orWhere('kategori', 'Pengambilan Service');
                    });
                    break;
                case 'sales':
                    $query->where('kategori', 'Penjualan');
                    break;
                case 'operational':
                    $query->where('kategori', 'LIKE', 'Operasional:%');
                    break;
                case 'store':
                    $query->where('kategori', 'Pengeluaran Toko');
                    break;
                case 'laci':
                    $query->where('kategori', 'LIKE', 'Laci:%');
                    break;
                case 'manual':
                    $query->whereNull('kode_referensi');
                    break;
            }
        }

        // Generate report sesuai tipe
        switch ($reportType) {
            case 'summary':
                $report = $this->generateSummaryReport($query, $startDate, $endDate);
                $view = 'admin.page.financial.pdf.summary';
                break;

            case 'detail':
                $report = $this->generateDetailReport($query, $startDate, $endDate);
                $view = 'admin.page.financial.pdf.detail';
                break;

            case 'category':
                $report = $this->generateCategoryReport($query, $startDate, $endDate);
                $view = 'admin.page.financial.pdf.category';
                break;

            default:
                $report = $this->generateSummaryReport($query, $startDate, $endDate);
                $view = 'admin.page.financial.pdf.summary';
        }

        $pdf = PDF::loadView($view, [
            'report' => $report,
            'owner' => $this->getThisUser()
        ]);

        $filename = 'laporan_keuangan_' . $reportType;
        if ($source) {
            $filename .= '_' . $source;
        }
        $filename .= '_' . $startDate . '_' . $endDate . '.pdf';

        return $pdf->download($filename);
    }

    // Export laporan ke Excel
    public function exportExcel(Request $request)
    {
        // Filter
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');
        $reportType = $request->input('report_type', 'summary');
        $source = $request->input('source');

        $filename = 'laporan_keuangan_' . $reportType;
        if ($source) {
            $filename .= '_' . $source;
        }
        $filename .= '_' . $startDate . '_' . $endDate . '.xlsx';

        return Excel::download(new FinancialExport(
            $startDate,
            $endDate,
            $reportType,
            $this->getThisUser()->id_upline,
            $source
        ), $filename);
    }

    // Integrasi dengan Transaksi Service
    public function createFromService($serviceId)
    {
        $service = Sevices::findOrFail($serviceId);

        // Cek apakah service milik owner yang sama
        if ($service->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.index')->with('error', 'Anda tidak memiliki akses ke service ini');
        }

        $page = "Tambah Transaksi dari Service";

        // Get kategori untuk dropdown
        $incomeCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                          ->where('tipe_kategori', 'Pemasukan')
                                          ->where('is_active', true)
                                          ->orderBy('nama_kategori')
                                          ->get();

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.create_from_service', compact(
            'page',
            'service',
            'incomeCategories',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }

    // Integrasi dengan transaksi penjualan
    public function createFromSale($saleId)
    {
        $sale = Penjualan::findOrFail($saleId);

        // Cek apakah penjualan milik owner yang sama
        if ($sale->kode_owner != $this->getThisUser()->id_upline) {
            return redirect()->route('financial.index')->with('error', 'Anda tidak memiliki akses ke penjualan ini');
        }

        $page = "Tambah Transaksi dari Penjualan";

        // Get kategori untuk dropdown
        $incomeCategories = FinancialCategory::where('kode_owner', $this->getThisUser()->id_upline)
                                          ->where('tipe_kategori', 'Pemasukan')
                                          ->where('is_active', true)
                                          ->orderBy('nama_kategori')
                                          ->get();

        // Get list of kategori laci
        $listLaci = $this->getKategoriLaci();

        $content = view('admin.page.financial.create_from_sale', compact(
            'page',
            'sale',
            'incomeCategories',
            'listLaci'
        ));
        // Return dengan blank_page layout
    return view('admin.layout.blank_page', compact('page', 'content'));
    }
}
