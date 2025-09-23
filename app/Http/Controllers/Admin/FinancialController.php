<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasPerusahaan;
use App\Models\FinancialCategory;
use App\Models\Sparepart;
use App\Models\Handphone;
use App\Models\Aset;
use App\Traits\OperationalDateTrait; // WAJIB: Impor Trait tanggal operasional
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF; // Asumsi Anda menggunakan library PDF
use Maatwebsite\Excel\Facades\Excel; // Asumsi Anda menggunakan Laravel Excel
use App\Exports\FinancialExport; // Asumsi export ini sudah disesuaikan

class FinancialController extends Controller
{
    use OperationalDateTrait; // REF-NOTE: Menggunakan Trait untuk semua logika tanggal

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

        // --- LOGIKA UNTUK LAPORAN HARIAN (TIDAK BERUBAH) ---
        $stats = $this->getFinancialStatsForDay($filterDate, $ownerId);
        $monthlyData = $this->getMonthlyDataForChart($year, $ownerId);
        $queryLatest = KasPerusahaan::where('kode_owner', $ownerId);
        $this->applyOperationalDateFilter($queryLatest, $filterDate, $ownerId);
        $latestTransactions = $queryLatest->orderBy('tanggal', 'desc')->limit(8)->get();

        // =================================================================
        //         LOGIKA BARU: MENGHITUNG TOTAL KEKAYAAN PERUSAHAAN
        // =================================================================

        // 1. Modal Uang (Saldo Akhir Kas)
        $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)->latest('id')->first()->saldo ?? 0;

        // 2. Modal Barang (Total Nilai Stok berdasarkan Harga Beli)
        $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
        $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
        $totalNilaiBarang = $nilaiSparepart + $nilaiHandphone;

        // 3. Modal Aset (Total Nilai Aset Tetap)
        // Pastikan Anda sudah membuat Model 'Aset' dan fungsionalitas CRUD-nya
        $totalNilaiAset = Aset::where('kode_owner', $ownerId)->sum('nilai_perolehan');

        // 4. Hitung Total Kekayaan
        $totalKekayaan = $saldoKas + $totalNilaiBarang + $totalNilaiAset;

        // Gabungkan dalam satu array untuk dikirim ke view
        $kekayaanStats = [
            'saldoKas' => $saldoKas,
            'totalNilaiBarang' => $totalNilaiBarang,
            'totalNilaiAset' => $totalNilaiAset,
            'totalKekayaan' => $totalKekayaan,
        ];

        $content = view('admin.page.financial.dashboard', compact(
            'page', 'stats', 'monthlyData', 'latestTransactions', 'filterDate',
            'year', 'closingTimeFormatted', 'kekayaanStats' // Tambahkan 'kekayaanStats'
        ));

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * REF-NOTE: SEMUA FUNGSI syncFinancialData() DAN TURUNANNYA TELAH DIHAPUS.
     * Sinkronisasi tidak lagi digunakan. Pencatatan kas dilakukan langsung di controller sumber.
     */

    /**
     * Menghitung statistik keuangan untuk satu hari operasional.
     */
    private function getFinancialStatsForDay(string $date, int $ownerId): array
    {
        $query = KasPerusahaan::where('kode_owner', $ownerId);
        $this->applyOperationalDateFilter($query, $date, $ownerId);

        $totalIncome = (clone $query)->sum('debit');
        $totalExpense = (clone $query)->sum('kredit');

        return [
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'netProfit' => $totalIncome - $totalExpense,
        ];
    }

    /**
     * Mengambil data agregat bulanan untuk ditampilkan di chart.
     */
    private function getMonthlyDataForChart(int $year, int $ownerId): array
    {
        $data = KasPerusahaan::where('kode_owner', $ownerId)
            ->whereYear('tanggal', $year)
            ->select(
                DB::raw('MONTH(tanggal) as month'),
                DB::raw('SUM(debit) as total_income'),
                DB::raw('SUM(kredit) as total_expense')
            )
            ->groupBy(DB::raw('MONTH(tanggal)'))
            ->get()->keyBy('month');

        $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Ags', 'Sep', 'Okt', 'Nov', 'Des'];
        $chartData = ['labels' => [], 'income' => [], 'expense' => [], 'profit' => []];

        foreach ($months as $i => $label) {
            $monthNum = $i + 1;
            $income = $data[$monthNum]->total_income ?? 0;
            $expense = $data[$monthNum]->total_expense ?? 0;
            $chartData['labels'][] = $label;
            $chartData['income'][] = $income;
            $chartData['expense'][] = $expense;
            $chartData['profit'][] = $income - $expense;
        }
        return $chartData;
    }

    /**
     * Menampilkan daftar semua transaksi (Buku Besar).
     */
    public function transactions(Request $request)
{
    $page = "Buku Besar Keuangan";
    $ownerId = $this->getOwnerId();

    // -- LOGIKA FILTER BARU --
    $year = $request->input('year', date('Y'));
    $month = $request->input('month');
    $type = $request->input('type');
    $source = $request->input('source');

    $query = KasPerusahaan::where('kode_owner', $ownerId)
                         ->orderBy('tanggal', 'desc')->orderBy('id', 'desc');

    if ($year) {
        $query->whereYear('tanggal', $year);
    }
    if ($month) {
        $query->whereMonth('tanggal', $month);
    }
    if ($type == 'Pemasukan') {
        $query->where('debit', '>', 0);
    }
    if ($type == 'Pengeluaran') {
        $query->where('kredit', '>', 0);
    }
    if ($source) {
        $sourceMap = [
            'modal' => 'App\\Models\\TransaksiModal',
            'service' => ['App\\Models\\Sevices', 'App\\Models\\Pengambilan'],
            'Penjualan' => 'App\\Models\\Penjualan',
            'operational' => 'App\\Models\\PengeluaranOperasional',
            'Pengeluaran' => 'App\\Models\\PengeluaranToko',
            'distribusi' => 'App\\Models\\DistribusiLaba',
            'pembelian' => 'App\\Models\\Pembelian',
            'manual' => null,
        ];

        if ($source == 'manual') {
            $query->whereNull('sourceable_type');
        } elseif (isset($sourceMap[$source])) {
            $query->whereIn('sourceable_type', (array)$sourceMap[$source]);
        }
    }

    $transactions = $query->paginate(25);

    // Ambil daftar tahun unik untuk dropdown filter
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
            if ($year > date('Y') || ($year == date('Y') && $month > date('m'))) {
                continue;
            }

            $endDate = Carbon::create($year, $month)->endOfMonth()->format('Y-m-d H:i:s');

            // 1. Snapshot Kekayaan (Tidak ada perubahan)
            $saldoKas = KasPerusahaan::where('kode_owner', $ownerId)->where('tanggal', '<=', $endDate)
                            ->orderBy('tanggal', 'desc')->orderBy('id', 'desc')->first()->saldo ?? 0;
            $nilaiSparepart = Sparepart::where('kode_owner', $ownerId)->sum(DB::raw('stok_sparepart * harga_beli'));
            $nilaiHandphone = Handphone::where('kode_owner', $ownerId)->sum(DB::raw('stok_barang * harga_beli_barang'));
            $totalNilaiBarang = $nilaiSparepart + $nilaiHandphone;
            $totalNilaiAset = Aset::where('kode_owner', $ownerId)->sum('nilai_perolehan');
            $totalKekayaan = $saldoKas + $totalNilaiBarang + $totalNilaiAset;

            // 2. Profit Bersih (Tidak ada perubahan)
            $baseQuery = KasPerusahaan::where('kode_owner', $ownerId)->whereYear('tanggal', $year)
                            ->whereMonth('tanggal', $month)
                            ->where(function ($query) {
                                $query->where('sourceable_type', '!=', 'App\\Models\\TransaksiModal')
                                    ->orWhereNull('sourceable_type');
                            });
            $monthlyIncome = (clone $baseQuery)->sum('debit');
            $monthlyExpense = (clone $baseQuery)->sum('kredit');
            $netProfit = $monthlyIncome - $monthlyExpense;

            // 3. --- LOGIKA BARU: Menghitung Alur Konversi Aset ---
            // Uang menjadi Barang (total belanja/pembelian stok di bulan ini)
            $cashToGoods = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
                ->where('sourceable_type', 'App\\Models\\Pembelian')
                ->sum('kredit');

            // Barang menjadi Uang (total pemasukan dari penjualan & service di bulan ini)
            // Berdasarkan kode Anda, sumbernya adalah Penjualan, Sevices, dan Pengambilan
            $goodsToCash = KasPerusahaan::where('kode_owner', $ownerId)
                ->whereYear('tanggal', $year)->whereMonth('tanggal', $month)
                ->whereIn('sourceable_type', ['App\\Models\\Penjualan', 'App\\Models\\Sevices', 'App\\Models\\Pengambilan'])
                ->sum('debit');

            $report[$month] = [
                'monthName' => Carbon::create()->month($month)->format('F'),
                'saldoKas' => $saldoKas,
                'totalNilaiBarang' => $totalNilaiBarang,
                'totalNilaiAset' => $totalNilaiAset,
                'totalKekayaan' => $totalKekayaan,
                'cashToGoods' => $cashToGoods, // Data baru
                'goodsToCash' => $goodsToCash, // Data baru
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
}
