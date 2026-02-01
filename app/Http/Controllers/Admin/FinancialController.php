<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasPerusahaan;
use App\Models\FinancialCategory;
use App\Models\Sparepart;
use App\Models\Handphone;
use App\Models\Aset;
use App\Services\FinancialService;
use App\Traits\OperationalDateTrait; // WAJIB: Impor Trait tanggal operasional
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF; // Asumsi Anda menggunakan library PDF
use Maatwebsite\Excel\Facades\Excel; // Asumsi Anda menggunakan Laravel Excel
use App\Exports\FinancialExport; // Asumsi export ini sudah disesuaikan
use Illuminate\Support\Facades\Cache;

class FinancialController extends Controller
{
    use OperationalDateTrait; // REF-NOTE: Menggunakan Trait untuk semua logika tanggal

    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    private array $nonExpenseSourceTypes = [
        'App\\Models\\Pembelian',           // Pembelian stok adalah konversi aset, bukan beban.
        'App\\Models\\TransaksiModal',       // Setoran/Tarikan modal adalah aktivitas pendanaan.
        'App\\Models\\DistribusiLaba',      // Pembagian profit adalah aktivitas pendanaan.
        'App\\Models\\PembayaranHutang',  // Jika ada, pembayaran pokok hutang bukan beban.
        'App\\Models\\PengeluaranOperasional',  // Beban tetap (gaji, sewa, listrik) yang ingin Anda pisahkan.
        'App\\Models\\AlokasiLaba',             // Digunakan saat pencairan, sama seperti DistribusiLaba.
        'App\\Models\\Penarikan',
        'App\\Models\\PengeluaranToko',
    ];

    /**
     * --- OPTIMIZED ---
     * Daftar sourceable_type yang BUKAN merupakan Pendapatan (Revenue) operasional.
     */
    private array $nonRevenueSourceTypes = [
        'App\\Models\\TransaksiModal',       // Setoran modal bukan pendapatan.
        // 'App\\Models\\PenerimaanHutang', // Jika ada, penerimaan pinjaman bukan pendapatan.
    ];
    /**
     * Mendapatkan ID Owner yang sedang login atau atasan dari karyawan yang login.
     */
    private function getOwnerId(): int
    {
        $user = Auth::user();
        // REF-NOTE: Pastikan relasi userDetail ada di model User Anda
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    /**
     * Menampilkan halaman dashboard keuangan utama.
     */
    public function index(Request $request)
    {
        $page = "Dashboard Keuangan";
        $ownerId = $this->getOwnerId();
        $filterDate = $request->input('date', now()->format('Y-m-d'));
        $year = Carbon::parse($filterDate)->year;
        $closingTimeFormatted = Carbon::parse($this->getClosingTime($ownerId))->format('H:i');

        // --- OPTIMIZED ---: Menggunakan statistik dashboard yang lebih lengkap ala POSY
        $stats = $this->getFinancialDashboardStats($ownerId, $filterDate);

        $monthlyData = $this->getMonthlyDataForChart($year, $ownerId);
        $queryLatest = KasPerusahaan::where('kode_owner', $ownerId);
        $this->applyOperationalDateFilter($queryLatest, $filterDate, $ownerId);
        $latestTransactions = $queryLatest->orderBy('tanggal', 'desc')->limit(8)->get();

        $content = view('admin.page.financial.dashboard', compact(
            'page', 'stats', 'monthlyData', 'latestTransactions', 'filterDate',
            'year', 'closingTimeFormatted'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menghitung statistik dashboard keuangan lengkap (mirip POSY).
     * Mencakup: Revenue, Expense, Profit, Asset, Modal Disetor.
     */
    private function getFinancialDashboardStats(int $ownerId, string $date): array
    {
        // 1. Profit Summary (Operational) - Menggunakan Trait
        // Hitung dari awal bulan ini sampai tanggal filter agar angka lebih bermakna (akumulatif bulan berjalan)
        // Atau ikuti filterDate (harian)?
        // Di RBM filter-nya harian. Kita ikuti harian dulu sesuai input date.
        
        $labaResult = $this->financialService->calculateNetProfit($ownerId, $date, $date);
        
        $totalRevenue = $labaResult['laba_kotor'] + $labaResult['detail_beban']['HPP (Modal Pokok Penjualan)']; // Revenue = Gross Profit + COGS
        
        $operatingExpenses = ($labaResult['detail_beban']['Biaya Operasional Insidental'] ?? 0) 
                             + ($labaResult['detail_beban']['Biaya Komisi Teknisi'] ?? 0)
                             + ($labaResult['detail_beban']['Beban Tetap Periodik'] ?? 0);
                             
        $depreciation = $labaResult['detail_beban']['Beban Penyusutan Aset'] ?? 0;
        $netProfit = $labaResult['laba_bersih'];

        // 2. Inventory Value (ASSET)
        $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
        $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
        $inventoryValue = $nilaiSparepart + $nilaiHandphone;

        // 3. Saldo Kas (ASSET)
        // Ambil saldo terakhir pada tanggal tersebut
        $endDate = Carbon::parse($date)->endOfDay();
        $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)
            ->where('tanggal', '<=', $endDate)
            ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')
            ->first()->saldo ?? 0;

        // 4. Modal Disetor (Paid In Capital)
        // Rumus: Total Setoran - Total Penarikan Modal
        $capitalIn = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
            ->whereIn('jenis_transaksi', ['setoran_awal', 'tambahan_modal'])
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');

        $capitalOut = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
            ->where('jenis_transaksi', 'penarikan_modal')
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');
            
        $paidInCapital = $capitalIn - $capitalOut;

        // 5. Total Asset
        // Asset = Kas + Inventory + Aset Tetap
        $asetTetap = Aset::where('kode_owner', $ownerId)
             ->where('tanggal_perolehan', '<=', $endDate)
             ->sum('nilai_perolehan');
             
        $totalAsset = $saldoKas + $inventoryValue + $asetTetap;

        return [
            'totalRevenue' => $totalRevenue,
            'inventoryValue' => $inventoryValue,
            'saldoKas' => $saldoKas,
            'operatingExpenses' => $operatingExpenses, // Cash expenses
            'depreciation' => $depreciation, // Non-cash
            'totalExpenseDisplay' => $operatingExpenses + $depreciation,
            'paidInCapital' => $paidInCapital,
            'netProfit' => $netProfit,
            'totalAsset' => $totalAsset,
            'asetTetap' => $asetTetap
        ];
    }

    private function getMonthlyDataForChart(int $year, int $ownerId): array
    {
        // --- OPTIMIZED ---: Perhitungan profit di chart juga dibuat lebih akurat
        $incomeData = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereYear('tanggal', $year)
            ->whereNotIn('sourceable_type', $this->nonRevenueSourceTypes) // <-- Logic Akurat
            ->select(DB::raw('MONTH(tanggal) as month'), DB::raw('SUM(debit) as total'))
            ->groupBy('month')->get()->keyBy('month');

        $expenseData = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereYear('tanggal', $year)
            ->whereNotIn('sourceable_type', $this->nonExpenseSourceTypes) // <-- Logic Akurat
            ->select(DB::raw('MONTH(tanggal) as month'), DB::raw('SUM(kredit) as total'))
            ->groupBy('month')->get()->keyBy('month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartData = ['labels' => [], 'income' => [], 'expense' => [], 'profit' => []];

        foreach ($months as $i => $label) {
            $monthNum = $i + 1;
            $income = $incomeData[$monthNum]->total ?? 0;
            $expense = $expenseData[$monthNum]->total ?? 0;
            $chartData['labels'][] = $label;
            $chartData['income'][] = $income;
            $chartData['expense'][] = $expense;
            $chartData['profit'][] = $income - $expense;
        }
        return $chartData;
    }

    public function transactions(Request $request)
    {
        $page = "Buku Besar Keuangan";
        $ownerId = $this->getOwnerId();

        $year = $request->input('year', date('Y'));
        $month = $request->input('month');
        $type = $request->input('type');
        $source = $request->input('source');

        $query = KasPerusahaan::where('kode_owner', $ownerId)
                             ->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

        if ($year) $query->whereYear('tanggal', $year);
        if ($month) $query->whereMonth('tanggal', $month);
        if ($type == 'Pemasukan') $query->where('debit', '>', 0);
        if ($type == 'Pengeluaran') $query->where('kredit', '>', 0);

        if ($source) {
            $sourceMap = [
                'modal' => 'App\\Models\\TransaksiModal',
                // --- OPTIMIZED ---: Typo 'Sevices' diperbaiki menjadi 'Services'
                'service' => ['App\\Models\\Sevices', 'App\\Models\\Pengambilan'],
                'penjualan' => 'App\\Models\\Penjualan', // Dibuat lowercase agar konsisten
                'operational' => 'App\\Models\\PengeluaranOperasional',
                'toko' => 'App\\Models\\PengeluaranToko', // Dibuat lowercase
                'distribusi' => 'App\\Models\\DistribusiLaba',
                'pembelian' => 'App\\Models\\Pembelian',
                'manual' => null,
            ];
            // Mengubah input source menjadi lowercase untuk pencocokan yang andal
            $sourceKey = strtolower($source);

            if ($sourceKey == 'manual') {
                $query->whereNull('sourceable_type');
            } elseif (isset($sourceMap[$sourceKey])) {
                $query->whereIn('sourceable_type', (array)$sourceMap[$sourceKey]);
            }
        }

        $transactions = $query->paginate(25);
        $years = KasPerusahaan::select(DB::raw('YEAR(tanggal) as year'))
                       ->where('kode_owner', $ownerId)->distinct()
                       ->orderBy('year', 'desc')->pluck('year');

        $content = view('admin.page.financial.transactions', compact(
            'page', 'transactions', 'years', 'year', 'month', 'type', 'source'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan form untuk menambah transaksi manual.
     */
    public function create()
    {
        $page = "Tambah Transaksi Manual";
        $ownerId = $this->getOwnerId();

        $incomeCategories = FinancialCategory::where('kode_owner', $ownerId)->where('tipe_kategori', 'Pemasukan')->where('is_active', true)->get();
        $expenseCategories = FinancialCategory::where('kode_owner', $ownerId)->where('tipe_kategori', 'Pengeluaran')->where('is_active', true)->get();

        $content = view('admin.page.financial.create', compact('page', 'incomeCategories', 'expenseCategories'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan transaksi manual baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'tipe_transaksi' => 'required|in:Pemasukan,Pengeluaran',
            'kategori' => 'required|string',
            'deskripsi' => 'nullable|string',
            'jumlah' => 'required|numeric|min:1',
        ]);

        $ownerId = $this->getOwnerId();

        DB::transaction(function () use ($request, $ownerId) {
            $saldoTerakhir = KasPerusahaan::where('kode_owner', $ownerId)->latest('id')->lockForUpdate()->first()->saldo ?? 0;
            $debit = ($request->tipe_transaksi == 'Pemasukan') ? $request->jumlah : 0;
            $kredit = ($request->tipe_transaksi == 'Pengeluaran') ? $request->jumlah : 0;

            KasPerusahaan::create([
                'tanggal' => $request->tanggal,
                'deskripsi' => $request->kategori . ($request->deskripsi ? ' - ' . $request->deskripsi : ''),
                'debit' => $debit,
                'kredit' => $kredit,
                'saldo' => $saldoTerakhir + $debit - $kredit,
                'kode_owner' => $ownerId,
                'sourceable_id' => null, // Tanda bahwa ini transaksi manual
                'sourceable_type' => null,
            ]);
        });

        return redirect()->route('financial.transactions')->with('success', 'Transaksi manual berhasil ditambahkan.');
    }

    /**
     * Menampilkan form edit untuk transaksi manual.
     */
    public function edit($id)
    {
        $page = "Edit Transaksi Manual";
        $ownerId = $this->getOwnerId();
        $transaction = KasPerusahaan::where('kode_owner', $ownerId)->findOrFail($id);

        $content = view('admin.page.financial.edit', compact('page', 'transaction'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Memperbarui deskripsi transaksi manual.
     * REF-NOTE: Hanya deskripsi yang boleh diubah untuk menjaga integritas saldo.
     */
    public function update(Request $request, $id)
    {
        $request->validate(['deskripsi' => 'required|string|max:255']);

        $ownerId = $this->getOwnerId();
        $transaction = KasPerusahaan::where('kode_owner', $ownerId)->findOrFail($id);

        // Hanya izinkan update pada transaksi manual
        if ($transaction->sourceable_type !== null) {
            return back()->with('error', 'Transaksi sistem tidak dapat diubah.');
        }

        $transaction->update(['deskripsi' => $request->deskripsi]);

        return redirect()->route('financial.transactions')->with('success', 'Deskripsi transaksi berhasil diperbarui.');
    }

    public function developmentReport(Request $request)
    {
        $page = "Laporan Perkembangan Bisnis";
        $ownerId = $this->getOwnerId();

        // Ambil tahun dari request, default ke tahun ini
        $year = $request->input('year', date('Y'));

        // Ambil daftar tahun unik untuk dropdown filter
        $availableYears = KasPerusahaan::select(DB::raw('YEAR(tanggal) as year'))
                            ->where('kode_owner', $ownerId)
                            ->distinct()
                            ->orderBy('year', 'desc')
                            ->pluck('year');

        // Ambil data perkembangan bulanan
        $developmentData = $this->getMonthlyDevelopmentData($year, $ownerId);

        $content = view('admin.page.financial.development_report', compact(
            'page', 'developmentData', 'year', 'availableYears'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Mengambil data snapshot kekayaan untuk setiap bulan dalam setahun.
     */
   private function getMonthlyDevelopmentData(int $year, int $ownerId): array
    {
        $report = [];
        $months = range(1, 12);

        foreach ($months as $month) {
            if ($year > date('Y') || ($year == date('Y') && $month > date('m'))) continue;

            // Tentukan tanggal awal dan akhir bulan
            $startDate = Carbon::create($year, $month, 1)->startOfMonth()->format('Y-m-d');
            $endDate = Carbon::create($year, $month, 1)->endOfMonth()->format('Y-m-d');

            // Saldo Kas di akhir bulan (sudah benar)
            $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)
                ->where('tanggal', '<=', $endDate)
                ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')
                ->first()->saldo ?? 0;

            // --- NOTE: Nilai Barang (Inventory) ---
            // Saat ini menggunakan nilai stok TERKINI karena belum ada sistem snapshot history inventory.
            // Idealnya menggunakan inventory ledger untuk mendapatkan nilai historis.
            $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
            $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
            $totalNilaiBarang = $nilaiSparepart + $nilaiHandphone;
            
            // Aset Tetap (bisa difilter berdasarkan tanggal perolehan)
            $totalNilaiAset = Aset::where('kode_owner', $ownerId)
                ->where('tanggal_perolehan', '<=', $endDate)
                ->sum('nilai_perolehan');
                
            $totalKekayaan = $saldoKas + $totalNilaiBarang + $totalNilaiAset;

            // --- REFACTORED: Menggunakan FinancialService untuk perhitungan Laba Bersih yang akurat ---
            // Menggunakan logika accrual (HPP) yang sama dengan dashboard dan laporan laba rugi
            $financialData = $this->financialService->calculateNetProfit($ownerId, $startDate, $endDate);
            $netProfit = $financialData['laba_bersih'];

            // Logika Alur Konversi Aset (Cash Flow stats)
            $cashToGoods = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->where('sourceable_type', 'App\\Models\\Pembelian')
                ->sum('kredit');
                
            $goodsToCash = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereBetween('tanggal', [$startDate, $endDate])
                ->whereIn('sourceable_type', ['App\\Models\\Penjualan', 'App\\Models\\Sevices', 'App\\Models\\Pengambilan'])
                ->sum('debit');

            $report[$month] = [
                'monthName' => Carbon::create()->month($month)->format('F'),
                'saldoKas' => $saldoKas,
                'totalNilaiBarang' => $totalNilaiBarang,
                'totalNilaiAset' => $totalNilaiAset,
                'totalKekayaan' => $totalKekayaan,
                'cashToGoods' => $cashToGoods,
                'goodsToCash' => $goodsToCash,
                'netProfit' => $netProfit
            ];
        }

        $chartData = [
            'labels' => [], 'saldoKas' => [], 'nilaiBarang' => [], 'nilaiAset' => [], 'totalKekayaan' => [],
        ];
        foreach ($report as $data) {
            $chartData['labels'][] = $data['monthName'];
            $chartData['saldoKas'][] = $data['saldoKas'];
            $chartData['nilaiBarang'][] = $data['totalNilaiBarang'];
            $chartData['nilaiAset'][] = $data['totalNilaiAset'];
            $chartData['totalKekayaan'][] = $data['totalKekayaan'];
        }

        return ['table' => $report, 'chart' => $chartData];
    }

    public function printDevelopmentReport(Request $request)
    {
        $page = "Cetak Laporan Perkembangan Bisnis";
        $ownerId = $this->getOwnerId();
        $year = $request->input('year', date('Y'));

        // Kita gunakan lagi method yang sudah ada untuk mengambil data
        $developmentData = $this->getMonthlyDevelopmentData($year, $ownerId);

        // Mengarahkan ke view baru yang khusus untuk dicetak
        return view('admin.page.financial.development_report_print', compact(
            'page',
            'developmentData',
            'year'
        ));
    }


    // new
    private function mapSourceTypeToCategoryName($sourceType)
    {
        if ($sourceType === null) {
            return 'Transaksi Manual';
        }

        $map = [
            'App\\Models\\Sevices' => 'Pendapatan Servis',
            'App\\Models\\Pengambilan' => 'Pengambilan Servis',
            'App\\Models\\Penjualan' => 'Penjualan Barang',
            'App\\Models\\TransaksiModal' => 'Transaksi Modal',
            'App\\Models\\DistribusiLaba' => 'Distribusi Laba / Prive',
            'App\\Models\\Pembelian' => 'Pembelian Stok Barang',
            'App\\Models\\Hutang' => 'Pembayaran Hutang Usaha',
            'App\\Models\\PengeluaranOperasional' => 'Biaya Operasional',
            'App\\Models\\PengeluaranToko' => 'Biaya Toko',
            // Tambahkan model lain jika ada
        ];

        // Cek jika ada kategori manual dari deskripsi
        if ($sourceType === 'manual_income') {
            return 'Pendapatan Manual';
        }
        if ($sourceType === 'manual_expense') {
            return 'Pengeluaran Manual';
        }

        return $map[$sourceType] ?? 'Lain-lain';
    }

    /**
     * Menampilkan Laporan Ringkasan Arus Kas (Cash Flow Summary).
     */
    public function cashFlowReport(Request $request)
    {
        $page = "Laporan Arus Kas";
        $ownerId = $this->getOwnerId();

        // Mengambil rentang tanggal dari request, default ke bulan ini
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // --- 1. Saldo Awal ---
        // Ambil saldo terakhir SEBELUM tanggal mulai
        $saldoAwal = KasPerusahaan::where('kode_owner', $ownerId)
            ->where('tanggal', '<', $startDate)
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first()->saldo ?? 0;

        // --- 2. Arus Kas Masuk (Kelompok per Kategori) ---
        $kasMasukQuery = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('debit', '>', 0);

        $totalKasMasuk = (clone $kasMasukQuery)->sum('debit');
        $detailKasMasuk = (clone $kasMasukQuery)
            ->select('sourceable_type', DB::raw("SUBSTRING_INDEX(deskripsi, ' - ', 1) as kategori_manual"), DB::raw('SUM(debit) as total'))
            ->groupBy('sourceable_type', 'kategori_manual')
            ->get()
            ->map(function ($item) {
                // Jika manual, gunakan kategori dari deskripsi
                if ($item->sourceable_type === null) {
                    $item->kategori = $item->kategori_manual;
                } else {
                    $item->kategori = $this->mapSourceTypeToCategoryName($item->sourceable_type);
                }
                return $item;
            })->groupBy('kategori')->map(function($group) {
                return $group->sum('total'); // Gabungkan kategori yang sama
            });


        // --- 3. Arus Kas Keluar (Kelompok per Kategori) ---
        $kasKeluarQuery = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->where('kredit', '>', 0);

        $totalKasKeluar = (clone $kasKeluarQuery)->sum('kredit');
        $detailKasKeluar = (clone $kasKeluarQuery)
            ->select('sourceable_type', DB::raw("SUBSTRING_INDEX(deskripsi, ' - ', 1) as kategori_manual"), DB::raw('SUM(kredit) as total'))
            ->groupBy('sourceable_type', 'kategori_manual')
            ->get()
            ->map(function ($item) {
                if ($item->sourceable_type === null) {
                    $item->kategori = $item->kategori_manual;
                } else {
                    $item->kategori = $this->mapSourceTypeToCategoryName($item->sourceable_type);
                }
                return $item;
            })->groupBy('kategori')->map(function($group) {
                return $group->sum('total');
            });

        // --- 4. Saldo Akhir ---
        // Saldo akhir adalah Saldo Awal + Total Masuk - Total Keluar
        $saldoAkhir = $saldoAwal + $totalKasMasuk - $totalKasKeluar;

        $reportData = [
            'startDate' => $startDate,
            'endDate' => $endDate,
            'saldoAwal' => $saldoAwal,
            'totalKasMasuk' => $totalKasMasuk,
            'detailKasMasuk' => $detailKasMasuk,
            'totalKasKeluar' => $totalKasKeluar,
            'detailKasKeluar' => $detailKasKeluar,
            'saldoAkhir' => $saldoAkhir,
        ];

        $content = view('admin.page.financial.cash_flow_report', compact('page', 'reportData'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }
    /**
     * Menampilkan Laporan Neraca Keuangan Komparatif.
     */
    public function balanceSheetReport(Request $request)
    {
        $page = "Laporan Neraca Keuangan Komparatif";
        $ownerId = $this->getOwnerId();

        // Ambil tanggal dari request, default ke awal dan akhir bulan ini
        $startDate = $request->input('start_date', now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', now()->endOfMonth()->format('Y-m-d'));

        // Hitung data neraca untuk tanggal akhir periode
        $endPeriodData = $this->calculateBalanceSheetForDate($ownerId, $endDate);

        // Hitung data neraca untuk tanggal awal periode
        $startPeriodData = $this->calculateBalanceSheetForDate($ownerId, $startDate);

        $content = view('admin.page.financial.balance_sheet_report', compact('page', 'startPeriodData', 'endPeriodData', 'startDate', 'endDate'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Helper function untuk menghitung semua komponen neraca pada tanggal tertentu.
     * Ini akan dipanggil dua kali: untuk tanggal awal dan tanggal akhir.
     */
    private function calculateBalanceSheetForDate(int $ownerId, string $asOfDate): array
    {
        $endDate = Carbon::parse($asOfDate)->endOfDay();

        // ASET
        $kas = KasPerusahaan::where('kode_owner', $ownerId)->where('tanggal', '<=', $endDate)
            ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first()->saldo ?? 0;
        $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
        $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
        $nilaiStok = $nilaiSparepart + $nilaiHandphone;
        $asetTetap = Aset::where('kode_owner', $ownerId)->sum('nilai_perolehan');
        $totalAset = $kas + $nilaiStok + $asetTetap;

        // KEWAJIBAN
        $utangKomisi = \App\Models\UserDetail::where('id_upline', $ownerId)->where('jabatan', 3)->sum('saldo');
        $utangDistribusi = \App\Models\AlokasiLaba::where('kode_owner', $ownerId)
                            ->where('status', 'dialokasikan')
                            ->where('created_at', '<=', $endDate)
                            ->sum('jumlah');
        $utangUsaha = \App\Models\Hutang::where('kode_owner', $ownerId)
                        ->where('status', '!=', 'Lunas')
                        ->where('created_at', '<=', $endDate)
                        ->sum('total_hutang');
        $totalKewajiban = $utangKomisi + $utangDistribusi + $utangUsaha;

        // MODAL
        $capitalIn = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
            ->whereIn('jenis_transaksi', ['setoran_awal', 'tambahan_modal'])
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');

        $capitalOut = \App\Models\TransaksiModal::where('kode_owner', $ownerId)
            ->where('jenis_transaksi', 'penarikan_modal')
            ->where('tanggal', '<=', $endDate)
            ->sum('jumlah');

        $modalDisetor = $capitalIn - $capitalOut;

        $labaResult = $this->financialService->calculateNetProfit($ownerId, '2020-01-01', $asOfDate);
        $labaBersihKumulatif = $labaResult['laba_bersih'];
        $totalLabaPernahDialokasikan = \App\Models\DistribusiLaba::where('kode_owner', $ownerId)
            ->where('tanggal', '<=', $endDate)
            ->sum('laba_bersih');
        $labaDitahan = $labaBersihKumulatif - $totalLabaPernahDialokasikan;
        $totalModal = $modalDisetor + $labaDitahan;

        // Kembalikan dalam bentuk array
        return [
            'aset' => [ 'kas' => $kas, 'nilaiStok' => $nilaiStok, 'asetTetap' => $asetTetap, 'total' => $totalAset ],
            'kewajiban' => [
                'utangUsaha' => $utangUsaha,
                'utangKomisi' => $utangKomisi,
                'utangDistribusi' => $utangDistribusi,
                'total' => $totalKewajiban,
            ],
            'modal' => [ 'modalDisetor' => $modalDisetor, 'labaDitahan' => $labaDitahan, 'total' => $totalModal ],
            'totalKewajibanDanModal' => $totalKewajiban + $totalModal,
        ];
    }
}
