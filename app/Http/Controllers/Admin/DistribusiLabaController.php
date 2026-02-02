<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailSparepartPenjualan;
use App\Models\DistribusiLaba;
use App\Models\DistribusiSetting;
use App\Models\KasPerusahaan;
use App\Models\Penarikan;
use App\Models\AlokasiLaba;
use App\Models\Shift;
use App\Traits\ManajemenKasTrait;
use App\Traits\OperationalDateTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Str;

class DistribusiLabaController extends Controller
{
    use ManajemenKasTrait;
    use OperationalDateTrait;

    private function getOwnerId()
    {
        $user = Auth::user();
        return ($user->userDetail->jabatan == '1') ? $user->id : $user->userDetail->id_upline;
    }

    public function index(Request $request)
    {
        $page = "Tutup Buku & Distribusi Laba";
        $ownerId = $this->getOwnerId();
        $settings = DistribusiSetting::where('kode_owner', $ownerId)->get()->keyBy('role');

        // --- LOGIKA BARU UNTUK FILTER DAN SUMMARY ---

        // 1. Tentukan rentang tanggal
        // Jika ada input dari user, gunakan itu. Jika tidak, gunakan rentang bulan ini.
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // 2. Query dasar untuk histori distribusi pada rentang tanggal yang dipilih
        $historiQuery = DistribusiLaba::where('kode_owner', $ownerId)
            ->whereBetween('tanggal_selesai', [$startDate, $endDate]);

        // 3. Ambil data histori dengan paginasi
        $histori = (clone $historiQuery)->orderBy('tanggal_selesai', 'desc')->paginate(10);

        // 4. Hitung total untuk summary (tanpa paginasi)
        $summary = (clone $historiQuery)->selectRaw("
                SUM(laba_bersih) as total_laba_bersih,
                SUM(alokasi_owner) as total_alokasi_owner,
                SUM(alokasi_investor) as total_alokasi_investor,
                SUM(alokasi_karyawan) as total_alokasi_karyawan,
                SUM(alokasi_kas_aset) as total_alokasi_kas_aset
            ")->first();

        // --- AKHIR LOGIKA BARU ---

        $content = view('admin.page.financial.distribusi.index', compact(
            'page',
            'settings',
            'histori',
            'summary',      // Kirim data summary ke view
            'startDate',    // Kirim tanggal filter ke view
            'endDate'       // Kirim tanggal filter ke view
        ));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function storeSetting(Request $request)
    {
        $request->validate([
            'persentase.owner' => 'required|numeric|min:0|max:100',
            'persentase.investor' => 'required|numeric|min:0|max:100',
            'persentase.karyawan_bonus' => 'required|numeric|min:0|max:100',
            'persentase.kas_aset' => 'required|numeric|min:0|max:100',
        ]);

        $totalPersen = array_sum($request->persentase);
        if (round($totalPersen, 2) != 100) {
            return back()->with('error', "Total persentase harus 100%, saat ini totalnya: {$totalPersen}%.");
        }

        $ownerId = $this->getOwnerId();
        foreach ($request->persentase as $role => $persen) {
            DistribusiSetting::updateOrCreate(
                ['kode_owner' => $ownerId, 'role' => $role],
                ['persentase' => $persen]
            );
        }
        return redirect()->route('distribusi.index')->with('success', 'Pengaturan persentase distribusi berhasil disimpan.');
    }

    public function prosesDistribusiHarian(Request $request)
    {
        // Validasi input tanggal
        $request->validate(['tanggal' => 'required|date']);

        $ownerId = $this->getOwnerId();
        $tanggalOperasional = $request->tanggal;

        // 1. Cek apakah laba untuk tanggal operasional ini sudah pernah didistribusikan
        $cekSudahDistribusi = DistribusiLaba::where('kode_owner', $ownerId)
                                ->where('tanggal_mulai', $tanggalOperasional)
                                ->where('tanggal_selesai', $tanggalOperasional)
                                ->exists();

        if ($cekSudahDistribusi) {
            return back()->with('error', 'Laba untuk tanggal ' . Carbon::parse($tanggalOperasional)->format('d/m/Y') . ' sudah didistribusikan sebelumnya.');
        }

        // 2. Hitung Laba Bersih untuk HARI OPERASIONAL yang dipilih menggunakan metode Akrual/HPP
        $labaResult = $this->hitungLabaBersih($tanggalOperasional, $tanggalOperasional);
        $labaBersihHarian = $labaResult['laba_bersih'];

        // 3. Jika tidak ada laba (rugi atau impas), batalkan proses
        if ($labaBersihHarian <= 0) {
            return back()->with('info', "Tidak ada laba bersih pada tanggal operasional " . Carbon::parse($tanggalOperasional)->format('d/m/Y') . ". Proses dibatalkan.");
        }

        // 4. Lanjutkan ke proses alokasi dan penyimpanan
        return $this->distribusikanLaba(
            $ownerId,
            $labaBersihHarian,
            $labaResult['laba_kotor'],
            $labaResult['total_pendapatan'],
            $tanggalOperasional,
            $tanggalOperasional
        );
    }

    public function prosesTutupBuku(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $ownerId = $this->getOwnerId();
        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        // ==========================================================
        //          👇 TAMBAHKAN BLOK VALIDASI TUMPANG TINDIH INI 👇
        // ==========================================================
        $cekTumpangTindih = DistribusiLaba::where('kode_owner', $ownerId)
            // Cari record yang rentangnya bersinggungan dengan rentang baru.
            // Kondisi: (akhir_lama >= awal_baru) AND (awal_lama <= akhir_baru)
            ->where('tanggal_selesai', '>=', $startDate)
            ->where('tanggal_mulai', '<=', $endDate)
            ->first();

        if ($cekTumpangTindih) {
            $pesanError = "Gagal memproses. Sebagian atau seluruh tanggal dalam rentang yang Anda pilih sudah pernah didistribusikan sebelumnya dalam periode " .
                        Carbon::parse($cekTumpangTindih->tanggal_mulai)->format('d/m/Y') . " - " .
                        Carbon::parse($cekTumpangTindih->tanggal_selesai)->format('d/m/Y') . ".";
            return back()->with('error', $pesanError);
        }
        // ==========================================================
        //                      ✅ AKHIR BLOK VALIDASI ✅
        // ==========================================================


        // Buat query dasar untuk rentang tanggal yang dipilih
        // PERBAIKAN KECIL: Argumen hitungLabaBersih harus string tanggal
        $labaResult = $this->hitungLabaBersih($request->start_date, $request->end_date);
        $labaBersihPeriodik = $labaResult['laba_bersih'];

        if ($labaBersihPeriodik <= 0) {
            return back()->with('info', "Tidak ada laba bersih pada periode yang dipilih. Proses dibatalkan.");
        }

        // Lanjutkan proses distribusi
        return $this->distribusikanLaba(
            $ownerId, $labaBersihPeriodik, $labaResult['laba_kotor'], $labaResult['total_pendapatan'], $startDate, $endDate
        );
    }

    /**
     * Fungsi terpusat untuk menghitung laba bersih dengan metode HPP.
     * Menerima query builder dari kas_perusahaan sebagai input.
     */
    private function hitungLabaBersih(string $tanggalMulai, string $tanggalSelesai): array
    {
        $ownerId = $this->getOwnerId();
        $startRange = \Carbon\Carbon::parse($tanggalMulai)->startOfDay();
        $endRange = \Carbon\Carbon::parse($tanggalSelesai)->endOfDay();

        // A. Perhitungan Pendapatan (Sudah Benar)
        $totalPendapatanPenjualan = \App\Models\Penjualan::where('kode_owner', $ownerId)->where('status_penjualan', '1')->whereBetween('updated_at', [$startRange, $endRange])->sum('total_penjualan');
        $totalPendapatanService = \App\Models\Sevices::where('kode_owner', $ownerId)->where('status_services', 'Diambil')->whereBetween('updated_at', [$startRange, $endRange])->sum('total_biaya');
        $totalPendapatan = $totalPendapatanPenjualan + $totalPendapatanService;

        // B. Perhitungan HPP (Sudah Benar)
        $penjualanIds = \App\Models\Penjualan::where('kode_owner', $ownerId)->where('status_penjualan', '1')->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppSparepartJual = \App\Models\DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_sparepart'));
        $hppBarangJual = \App\Models\DetailBarangPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_barang'));
        $serviceIdsDiambil = \App\Models\Sevices::where('kode_owner', $ownerId)->where('status_services', 'Diambil')->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $hppPartTokoService = \App\Models\DetailPartServices::whereIn('kode_services', $serviceIdsDiambil)->sum(DB::raw('detail_modal_part_service * qty_part'));
        $hppPartLuarService = \App\Models\DetailPartLuarService::whereIn('kode_services', $serviceIdsDiambil)->sum(DB::raw('harga_part * qty_part'));
        $totalHpp = $hppSparepartJual + $hppBarangJual + $hppPartTokoService + $hppPartLuarService;

        // C. Laba Kotor (Sudah Benar)
        $labaKotor = $totalPendapatan - $totalHpp;

        // D. Biaya Variabel (Sudah Benar)
        $biayaOperasionalInsidental = \App\Models\PengeluaranToko::where('kode_owner', $ownerId)
            ->whereBetween('tanggal_pengeluaran', [$startRange, $endRange])
            ->sum('jumlah_pengeluaran');
        $serviceIdsSelesai = \App\Models\Sevices::where('kode_owner', $ownerId)->whereIn('status_services', ['Selesai','Diambil'])->whereBetween('updated_at', [$startRange, $endRange])->pluck('id');
        $biayaKomisi = \App\Models\ProfitPresentase::whereIn('kode_service', $serviceIdsSelesai)->sum('profit');


        // ======================================================================
        //          👇 BLOK PERHITUNGAN BEBAN TETAP & PENYUSUTAN YANG DIPERBARUI 👇
        // ======================================================================
        $jumlahHariPeriode = $startRange->diffInDays($endRange) + 1;

        // 🌟 PENAMBAHAN: PERHITUNGAN BEBAN GAJI TETAP 🌟
        $userIds = \App\Models\UserDetail::where('id_upline', $ownerId)->pluck('kode_user');
        $totalGajiTetapBulanan = \App\Models\SalarySetting::whereIn('user_id', $userIds)
            ->where('compensation_type', 'fixed')
            ->sum('basic_salary');
        $bebanGajiHarian = ($startRange->daysInMonth > 0) ? $totalGajiTetapBulanan / $startRange->daysInMonth : 0;
        $bebanGajiTetapPeriodik = $bebanGajiHarian * $jumlahHariPeriode;


        // E.1 Beban dari Aset Tetap (Penyusutan)
        $totalPenyusutanBulanan = \App\Models\Aset::where('kode_owner', $ownerId)->sum(DB::raw('(nilai_perolehan - nilai_residu) / masa_manfaat_bulan'));
        $bebanPenyusutanHarian = ($startRange->daysInMonth > 0) ? $totalPenyusutanBulanan / $startRange->daysInMonth : 0;
        $bebanPenyusutanPeriodik = $bebanPenyusutanHarian * $jumlahHariPeriode;

        // E.2 Beban dari Operasional Tetap (Bulanan & Tahunan) - Logika Baru
        $totalBebanBulanan = \App\Models\BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'bulanan')->sum('nominal');
        $totalBebanTahunan = \App\Models\BebanOperasional::where('kode_owner', $ownerId)
            ->where('periode', 'tahunan')->sum('nominal');

        $bebanHarianDariBulanan = ($startRange->daysInMonth > 0) ? $totalBebanBulanan / $startRange->daysInMonth : 0;
        $bebanHarianDariTahunan = ($startRange->daysInYear > 0) ? $totalBebanTahunan / $startRange->daysInYear : 0;

        $totalBebanTetapHarian = $bebanHarianDariBulanan + $bebanHarianDariTahunan;
        $bebanTetapPeriodik = $totalBebanTetapHarian * $jumlahHariPeriode;
        // ======================================================================

        // F. Hitung Laba Bersih Final
        $labaBersih = $labaKotor - $biayaOperasionalInsidental - $biayaKomisi - $bebanPenyusutanPeriodik - $bebanTetapPeriodik;

        Log::info('--- PERHITUNGAN LABA BERSIH AKRUAL (DistribusiLabaController) ---', [
            'Periode' => $startRange->format('Y-m-d') . ' to ' . $endRange->format('Y-m-d'),
            'Total Pendapatan Diakui' => $totalPendapatan, 'Total HPP' => $totalHpp, 'Laba Kotor' => $labaKotor,
            'Biaya Op. Insidental' => $biayaOperasionalInsidental, 'Biaya Komisi Teknisi' => $biayaKomisi,
            'Beban Penyusutan' => $bebanPenyusutanPeriodik, 'Beban Tetap' => $bebanTetapPeriodik,
            'Laba Bersih Final' => $labaBersih
        ]);

        return [
            'laba_bersih' => $labaBersih,
            'laba_kotor' => $labaKotor,
            'total_pendapatan' => $totalPendapatan,
            'beban' => [
                'Biaya Operasional Insidental' => $biayaOperasionalInsidental,
                'Biaya Komisi Teknisi' => $biayaKomisi,
                'Beban Gaji Tetap' => $bebanGajiTetapPeriodik,
                'Beban Penyusutan Periodik' => $bebanPenyusutanPeriodik,
                'Beban Tetap Periodik' => $bebanTetapPeriodik,
            ]
        ];
    }

    /**
     * Fungsi terpusat untuk memproses alokasi dan penyimpanan distribusi laba.
     */
    private function distribusikanLaba($ownerId, $labaBersih, $labaKotor, $pemasukan, $tanggalMulai, $tanggalSelesai)
    {
        $settings = DistribusiSetting::where('kode_owner', $ownerId)->get()->keyBy('role');
        if ($settings->isEmpty() || round($settings->sum('persentase'), 2) != 100) {
            return back()->with('error', 'Pengaturan persentase distribusi belum lengkap atau totalnya bukan 100%.');
        }

        DB::beginTransaction();
        try {
            $alokasi = [
                'owner' => $labaBersih * ($settings['owner']->persentase / 100),
                'investor' => $labaBersih * ($settings['investor']->persentase / 100),
                'karyawan_bonus' => $labaBersih * ($settings['karyawan_bonus']->persentase / 100),
                'kas_aset' => $labaBersih * ($settings['kas_aset']->persentase / 100),
            ];

            // 1. Buat log utama
            $distribusi = DistribusiLaba::create([
                'laba_kotor' => $labaKotor, 'laba_bersih' => $labaBersih,
                'alokasi_owner' => $alokasi['owner'], 'alokasi_investor' => $alokasi['investor'],
                'alokasi_karyawan' => $alokasi['karyawan_bonus'], 'alokasi_kas_aset' => $alokasi['kas_aset'],
                'kode_owner' => $ownerId, 'tanggal' => now(),
                'tanggal_mulai' => $tanggalMulai, 'tanggal_selesai' => $tanggalSelesai,
            ]);

            // ==========================================================
            //         PERBAIKAN: CATAT SEMUA ALOKASI SECARA DETAIL
            // ==========================================================

            // 2. Buat catatan alokasi detail untuk setiap peran
            if ($alokasi['owner'] > 0) {
                AlokasiLaba::create([
                    'distribusi_laba_id' => $distribusi->id,
                    'kode_owner' => $ownerId,
                    'user_id' => $ownerId, // User ID untuk owner
                    'role' => 'owner',
                    'jumlah' => $alokasi['owner'],
                ]);
            }

            // TAMBAHKAN BLOK INI UNTUK INVESTOR
            if ($alokasi['investor'] > 0) {
                AlokasiLaba::create([
                    'distribusi_laba_id' => $distribusi->id,
                    'kode_owner' => $ownerId,
                    'user_id' => null, // Untuk sementara null, bisa dikembangkan nanti
                    'role' => 'investor',
                    'jumlah' => $alokasi['investor'],
                ]);
            }

            // TAMBAHKAN BLOK INI UNTUK BONUS KARYAWAN
            if ($alokasi['karyawan_bonus'] > 0) {
                AlokasiLaba::create([
                    'distribusi_laba_id' => $distribusi->id,
                    'kode_owner' => $ownerId,
                    'user_id' => null, // Untuk sementara null, karena ini bonus umum
                    'role' => 'karyawan_bonus',
                    'jumlah' => $alokasi['karyawan_bonus'],
                ]);
            }

            if ($alokasi['kas_aset'] > 0) {
                AlokasiLaba::create([
                    'distribusi_laba_id' => $distribusi->id,
                    'kode_owner' => $ownerId,
                    'user_id' => null, // Kas Aset tidak dimiliki perorangan
                    'role' => 'kas_aset',
                    'jumlah' => $alokasi['kas_aset'],
                ]);
            }

            DB::commit();
            return redirect()->route('distribusi.laporan')->with('success', "Distribusi laba berhasil dialokasikan. Saldo kas perusahaan tidak berubah.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan saat alokasi: ' . $e->getMessage());
        }
    }

    private function catatDistribusiDiKas(DistribusiLaba $distribusi, float $jumlah, string $deskripsi)
    {
        $ownerId = $distribusi->kode_owner;
        $saldoTerakhir = KasPerusahaan::where('kode_owner', $ownerId)->latest('id')->lockForUpdate()->first()->saldo ?? 0;
        $saldoBaru = $saldoTerakhir - $jumlah;

        $distribusi->kasEntries()->create([
            'kode_owner' => $ownerId, 'tanggal' => now(),
            'deskripsi' => $deskripsi, 'debit' => 0, 'kredit' => $jumlah, 'saldo' => $saldoBaru,
        ]);
    }

    public function laporan(Request $request)
    {
        $page = "Laporan Distribusi Laba";
        $ownerId = $this->getOwnerId();

        // QUERY YANG LEBIH SEDERHANA DAN AMAN
        $query = AlokasiLaba::where('kode_owner', $ownerId)
                           ->with('distribusiLaba')
                           ->orderBy('created_at', 'desc');

        // Filter Laporan
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('distribusiLaba', function ($q) use ($request) {
                $q->whereBetween('tanggal_selesai', [$request->start_date, $request->end_date]);
            });
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $alokasiLogs = $query->paginate(20);

        $content = view('admin.page.financial.distribusi.laporan', compact('page', 'alokasiLogs'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function pencairan()
    {
        $page = "Pencairan Dana Alokasi Laba";
        $ownerId = $this->getOwnerId();

        // Hitung total saldo yang tersedia untuk setiap pos
        $saldoTersedia = AlokasiLaba::where('kode_owner', $ownerId)
            ->where('status', 'dialokasikan')
            ->select('role', DB::raw('SUM(jumlah) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $content = view('admin.page.financial.distribusi.pencairan', compact('page', 'saldoTersedia'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Memproses aksi pencairan dana.
     */
    public function prosesPencairan(Request $request)
    {
        $request->validate([
            'role' => 'required|string',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string',
        ]);

        $ownerId = $this->getOwnerId();
        $role = $request->role;
        $jumlahPenarikan = $request->jumlah;

        $saldoTersedia = AlokasiLaba::where('kode_owner', $ownerId)
            ->where('role', $role)
            ->where('status', 'dialokasikan')
            ->sum('jumlah');

        if ($jumlahPenarikan > $saldoTersedia) {
            return back()->with('error', 'Jumlah penarikan melebihi saldo yang tersedia untuk pos ' . $role);
        }

        DB::beginTransaction();
        try {
            // Update status alokasi yang relevan menjadi "ditarik"
            $alokasiTersedia = AlokasiLaba::where('role', $role)->where('status', 'dialokasikan')
                ->whereHas('distribusiLaba', function ($q) use ($ownerId) {
                    $q->where('kode_owner', $ownerId);
                })->orderBy('created_at', 'asc')->get();

            $sisaUntukDitarik = $jumlahPenarikan;
            foreach ($alokasiTersedia as $alokasi) {
                if ($sisaUntukDitarik <= 0) break;

                $bisaDiambilDariAlokasiIni = min($alokasi->jumlah, $sisaUntukDitarik);

                // LANGSUNG CATAT PENGELUARAN DI BUKU BESAR
                $this->catatKas(
                    $alokasi, // Model sumbernya adalah AlokasiLaba
                    0, // Debit
                    $bisaDiambilDariAlokasiIni, // Kredit
                    'Pencairan Laba: ' . Str::title(str_replace('_', ' ', $alokasi->role)) . ($request->keterangan ? ' - ' . $request->keterangan : ''),
                    now()
                );

                if ($bisaDiambilDariAlokasiIni >= $alokasi->jumlah) {
                    $alokasi->update(['status' => 'ditarik']);
                } else {
                    $alokasi->decrement('jumlah', $bisaDiambilDariAlokasiIni);
                }
                $sisaUntukDitarik -= $bisaDiambilDariAlokasiIni;
            }

            DB::commit();
            return redirect()->route('distribusi.pencairan')->with('success', 'Dana berhasil dicairkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menghitung dan memberikan preview potensi distribusi laba harian.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function previewDistribusiHarian(Request $request)
    {
        // 1. Validasi input tanggal
        $request->validate(['tanggal' => 'required|date']);
        $ownerId = $this->getOwnerId();
        $tanggalOperasional = $request->tanggal;

        // 2. Cek pengaturan persentase
        $settings = DistribusiSetting::where('kode_owner', $ownerId)->get()->keyBy('role');
        if ($settings->isEmpty() || round($settings->sum('persentase'), 2) != 100) {
            return response()->json(['status' => 'error', 'message' => 'Pengaturan persentase distribusi belum lengkap atau totalnya bukan 100%.'], 400);
        }

        // 3. Hitung Laba Bersih dan dapatkan semua komponennya
        $labaResult = $this->hitungLabaBersih($tanggalOperasional, $tanggalOperasional);
        $labaBersihHarian = $labaResult['laba_bersih'];
        $labaKotor = $labaResult['laba_kotor'];
        $beban = $labaResult['beban']; // Ambil data beban

        // 4. Jika tidak ada laba, kirim response
        if ($labaBersihHarian <= 0) {
            return response()->json([
                'status' => 'info',
                'laba_kotor' => $labaKotor,
                'beban' => $beban,
                'laba_bersih' => $labaBersihHarian,
                'message' => 'Tidak ada laba bersih (atau rugi) pada tanggal yang dipilih.'
            ]);
        }

        // 5. Hitung potensi alokasi
        $potensiAlokasi = [];
        foreach ($settings as $role => $setting) {
            $label = Str::title(str_replace('_', ' ', $role));
            $potensiAlokasi[$label] = $labaBersihHarian * ($setting->persentase / 100);
        }

        // 6. Kembalikan data lengkap sebagai JSON
        return response()->json([
            'status' => 'success',
            'laba_kotor' => $labaKotor,
            'beban' => $beban,
            'laba_bersih' => $labaBersihHarian,
            'potensi_alokasi' => $potensiAlokasi
        ]);
    }
}
