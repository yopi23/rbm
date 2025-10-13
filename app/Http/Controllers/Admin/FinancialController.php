<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasPerusahaan;
use App\Models\FinancialCategory;
use App\Models\Sparepart;
use App\Models\Handphone;
use App\Models\Aset;
use App\Traits\OperationalDateTrait; // WAJIB: Impor Trait tanggal operasional
use App\Traits\ProfitCalculationTrait;
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
    use OperationalDateTrait, ProfitCalculationTrait; // REF-NOTE: Menggunakan Trait untuk semua logika tanggal

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

        // --- OPTIMIZED ---: Perhitungan profit sekarang lebih akurat
        $stats = $this->getFinancialStatsForDay($filterDate, $ownerId);

        $monthlyData = $this->getMonthlyDataForChart($year, $ownerId);
        $queryLatest = KasPerusahaan::where('kode_owner', $ownerId);
        $this->applyOperationalDateFilter($queryLatest, $filterDate, $ownerId);
        $latestTransactions = $queryLatest->orderBy('tanggal', 'desc')->limit(8)->get();

        // --- OPTIMIZED ---: Menerapkan Caching untuk performa
        // Cache akan menyimpan hasil perhitungan selama 15 menit.
        // Key cache unik untuk setiap owner.
        $wealthData = Cache::remember('wealth_stats_' . $ownerId, now()->addMinutes(5), function () use ($ownerId) {
            $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
            $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
            $totalNilaiAset = Aset::where('kode_owner', $ownerId)->sum('nilai_perolehan');
            return [
                'totalNilaiBarang' => $nilaiSparepart + $nilaiHandphone,
                'totalNilaiAset' => $totalNilaiAset,
            ];
        });

        $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)->latest('id')->first()->saldo ?? 0;
        $totalKekayaan = $saldoKas + $wealthData['totalNilaiBarang'] + $wealthData['totalNilaiAset'];

        $kekayaanStats = [
            'saldoKas' => $saldoKas,
            'totalNilaiBarang' => $wealthData['totalNilaiBarang'],
            'totalNilaiAset' => $wealthData['totalNilaiAset'],
            'totalKekayaan' => $totalKekayaan,
        ];

        $content = view('admin.page.financial.dashboard', compact(
            'page', 'stats', 'monthlyData', 'latestTransactions', 'filterDate',
            'year', 'closingTimeFormatted', 'kekayaanStats'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }


    /**
     * --- OPTIMIZED ---
     * Menghitung statistik keuangan (Laba/Rugi) yang lebih akurat untuk satu hari operasional.
     * Transaksi non-operasional seperti tambah modal atau pembelian stok tidak lagi dihitung.
     */
    // private function getFinancialStatsForDay(string $date, int $ownerId): array
    // {
    //     $query = KasPerusahaan::where('kode_owner', $ownerId);
    //     $this->applyOperationalDateFilter($query, $date, $ownerId);

    //     // Menghitung Pemasukan (Revenue), mengabaikan setoran modal.
    //     $totalIncome = (clone $query)
    //         ->whereNotIn('sourceable_type', $this->nonRevenueSourceTypes)
    //         ->sum('debit');

    //     // Menghitung Beban (Expense), mengabaikan pembelian, modal, dll.
    //     $totalExpense = (clone $query)
    //         ->whereNotIn('sourceable_type', $this->nonExpenseSourceTypes)
    //         ->sum('kredit');

    //     $totalFixedExpense = (clone $query)
    //         ->where('sourceable_type', 'App\\Models\\PengeluaranOperasional')
    //         ->sum('kredit');

    //     return [
    //         'totalIncome' => $totalIncome,
    //         'totalExpense' => $totalExpense,
    //         'totalFixedExpense' => $totalFixedExpense,
    //         'netProfit' => $totalIncome - $totalExpense,
    //     ];
    // }

    private function getFinancialStatsForDay(string $date, int $ownerId): array
    {
        // =================================================================================
        // BAGIAN 1: HITUNG LABA BERSIH OPERASIONAL SECARA AKURAT (AKRUAL)
        // Menggunakan trait yang sudah kita bahas untuk mendapatkan profitabilitas bisnis sesungguhnya.
        // =================================================================================
        $labaResult = $this->calculateNetProfit($ownerId, $date, $date);

        // Ambil data Pemasukan dan Biaya Variabel dari hasil perhitungan laba.
        $totalIncome = $labaResult['laba_kotor'] + $labaResult['detail_beban']['HPP (Modal Pokok Penjualan)'];
        $variableExpense = $labaResult['detail_beban']['Biaya Operasional Insidental'] ?? 0;
        $netProfit = $labaResult['laba_bersih'];


        // =================================================================================
        // BAGIAN 2: HITUNG SEMUA ARUS KAS KELUAR NON-OPERASIONAL (CASH BASIS)
        // Query ini sekarang hanya bertujuan untuk melihat uang keluar untuk hal di luar operasional.
        // =================================================================================
        $queryKas = KasPerusahaan::where('kode_owner', $ownerId);
        $this->applyOperationalDateFilter($queryKas, $date, $ownerId); // Filter sesuai hari operasional

        // a. Beban Tetap (Gaji, Sewa, Listrik, dll.)
        $totalFixedExpense = (clone $queryKas)
            ->where('sourceable_type', 'App\\Models\\PengeluaranOperasional')
            ->sum('kredit');

        // b. Penarikan Saldo oleh Karyawan
        $totalEmployeeWithdrawals = (clone $queryKas)
            ->where('sourceable_type', 'App\\Models\\Penarikan')
            ->sum('kredit');

        // c. Pembelian Stok (Aset)
        $totalStockPurchase = (clone $queryKas)
            ->where('sourceable_type', 'App\\Models\\Pembelian')
            ->sum('kredit');

        // d. Penarikan oleh Owner (Prive)
        $totalOwnerWithdrawals = (clone $queryKas)
            ->whereIn('sourceable_type', ['App\\Models\\DistribusiLaba', 'App\\Models\\AlokasiLaba'])
            ->sum('kredit');

        // Mengembalikan array data yang sudah rapi dan sesuai logika baru ke view.
        return [
            // Data Utama untuk 3 Kartu Teratas (Scope Laba Rugi Operasional)
            'totalIncome'  => $totalIncome,         // Mengisi "Pemasukan Operasional"
            'totalExpense' => $variableExpense,      // Mengisi "Pengeluaran Variabel" (hanya biaya toko/insidental)
            'netProfit'    => $netProfit,            // Mengisi "Laba BERSIH Operasional"

            // Data Tambahan untuk Rincian Arus Kas Keluar Non-Operasional
            'totalFixedExpense'        => $totalFixedExpense,
            'totalEmployeeWithdrawals' => $totalEmployeeWithdrawals,
            'totalStockPurchase'       => $totalStockPurchase,
            'totalOwnerWithdrawals'    => $totalOwnerWithdrawals
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

            $endDate = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d H:i:s');

            // Saldo Kas di akhir bulan (sudah benar)
            $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)->where('tanggal', '<=', $endDate)
                            ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first()->saldo ?? 0;

            // --- NOTE ---: PERINGATAN LOGIKA
            // Perhitungan di bawah ini mengambil nilai total stok SAAT INI, bukan nilai stok historis
            // pada akhir bulan yang bersangkutan. Untuk akurasi 100%, diperlukan sistem inventory ledger.
            $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
            $nilaiHandphone = Handphone::where('kode_owner', 'ownerId')->sum(DB::raw('stok_barang * harga_beli_barang'));
            $totalNilaiBarang = $nilaiSparepart + $nilaiHandphone;
            $totalNilaiAset = Aset::where('kode_owner', $ownerId)->sum('nilai_perolehan');
            $totalKekayaan = $saldoKas + $totalNilaiBarang + $totalNilaiAset;

            // --- OPTIMIZED ---: Profit bersih bulanan menggunakan logika yang lebih akurat
            $monthlyIncome = KasPerusahaan::where('kode_owner', $ownerId)->whereYear('tanggal', $year)
                            ->whereMonth('tanggal', $month)
                            ->whereNotIn('sourceable_type', $this->nonRevenueSourceTypes)
                            ->sum('debit');
            $monthlyExpense = KasPerusahaan::where('kode_owner', $ownerId)->whereYear('tanggal', $year)
                             ->whereMonth('tanggal', $month)
                             ->whereNotIn('sourceable_type', $this->nonExpenseSourceTypes)
                             ->sum('kredit');
            $netProfit = $monthlyIncome - $monthlyExpense;

            // Logika Alur Konversi Aset (sudah baik)
            $cashToGoods = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
                ->where('sourceable_type', 'App\\Models\\Pembelian')
                ->sum('kredit');
            $goodsToCash = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
                // --- OPTIMIZED ---: Typo 'Sevices' diperbaiki
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

        // (Verifikasi saldo akhir dengan data terakhir di database untuk periode tsb)
        $lastTransaction = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereBetween('tanggal', [$startDate, $endDate])
            ->orderBy('tanggal', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        // Jika ada transaksi di periode tsb, gunakan saldo terakhirnya.
        // Jika tidak, saldo akhir = saldo awal.
        $saldoAkhirDatabase = $lastTransaction ? $lastTransaction->saldo : $saldoAwal;

        // Kita gunakan saldo akhir dari perhitungan A + B - C agar lebih konsisten
        // $saldoAkhir = $saldoAkhirDatabase; // Anda bisa pilih salah satu

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
        $modalDisetor = KasPerusahaan::where('kode_owner', $ownerId)
            ->where('tanggal', '<=', $endDate)
            ->where('sourceable_type', 'App\\Models\\TransaksiModal')
            ->where('debit', '>', 0)->sum('debit');

        $labaResult = $this->calculateNetProfit($ownerId, '2020-01-01', $asOfDate);
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
