<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Carbon\Carbon;
use App\Models\DistribusiLaba;
use App\Models\DistribusiSetting;
use App\Models\AlokasiLaba;
use App\Traits\ManajemenKasTrait;
use App\Traits\HasOwnerAccess;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FinancialService;

class ProfitDistributionApiController extends Controller
{
    use ManajemenKasTrait, HasOwnerAccess;

    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function getProfitAllocationPreview(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
            ]);

            $kode_owner = $this->getThisUser()->id_upline;
            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;

            $statusDistribusi = 'belum_didistribusikan';
            $tanggalDistribusi = null;

            $cekSudahDistribusi = DistribusiLaba::where('kode_owner', $kode_owner)
                                ->where('tanggal_mulai', $tgl_awal)
                                ->where('tanggal_selesai', $tgl_akhir)
                                ->first();

            if ($cekSudahDistribusi) {
                $statusDistribusi = 'sudah_didistribusikan';
                $tanggalDistribusi = $cekSudahDistribusi->created_at->format('d M Y, H:i');
            }

            // 1. Cek pengaturan distribusi
            $settings = DistribusiSetting::where('kode_owner', $kode_owner)->get()->keyBy('role');
            if ($settings->isEmpty() || round($settings->sum('persentase'), 2) != 100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pengaturan persentase distribusi belum lengkap atau totalnya bukan 100%.'
                ], 400);
            }

            // 2. Hitung Laba Bersih
            $labaResult = $this->financialService->calculateNetProfit($kode_owner, $tgl_awal, $tgl_akhir);
            $labaBersih = $labaResult['laba_bersih'];

            // 3. Siapkan data response
            $potensiAlokasi = [];
            if ($labaBersih > 0) {
                foreach ($settings as $role => $setting) {
                    $potensiAlokasi[] = [
                        'role' => Str::title(str_replace('_', ' ', $role)),
                        'persentase' => (float) $setting->persentase,
                        'jumlah' => $labaBersih * ($setting->persentase / 100),
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'status_distribusi' => $statusDistribusi,
                    'tanggal_distribusi' => $tanggalDistribusi,
                    'laba_kotor' => $labaResult['laba_kotor'],
                    'beban_details' => $labaResult['detail_beban'], // Adjusted key from Trait
                    'laba_bersih' => $labaBersih,
                    'is_distributable' => $labaBersih > 0,
                    'potensi_alokasi' => $potensiAlokasi,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('API Get Profit Allocation Preview Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function processProfitDistribution(Request $request)
    {
        try {
            $request->validate([
                'tgl_awal' => 'required|date',
                'tgl_akhir' => 'required|date|after_or_equal:tgl_awal',
            ]);

            $kode_owner = $this->getThisUser()->id_upline;
            $tgl_mulai = Carbon::parse($request->tgl_awal)->startOfDay();
            $tgl_selesai = Carbon::parse($request->tgl_akhir)->endOfDay();

           $cekTumpangTindih = DistribusiLaba::where('kode_owner', $kode_owner)
            ->where('tanggal_selesai', '>=', $tgl_mulai)
            ->where('tanggal_mulai', '<=', $tgl_selesai)
            ->first();

            if ($cekTumpangTindih) {
                $pesanError = "Laba untuk periode ini tumpang tindih dengan distribusi yang sudah ada pada " .
                            Carbon::parse($cekTumpangTindih->tanggal_mulai)->format('d/m/Y') . " - " .
                            Carbon::parse($cekTumpangTindih->tanggal_selesai)->format('d/m/Y') . ".";
                return response()->json(['success' => false, 'message' => $pesanError], 409); // 409 Conflict
            }

            // 2. Hitung Laba Bersih
            $labaResult = $this->financialService->calculateNetProfit($kode_owner, $request->tgl_awal, $request->tgl_akhir);
            $labaBersih = $labaResult['laba_bersih'];

            if ($labaBersih <= 0) {
                return response()->json(['success' => false, 'message' => 'Tidak ada laba bersih pada periode yang dipilih. Proses dibatalkan.'], 400);
            }

            // 3. Proses Distribusi (adaptasi dari DistribusiLabaController)
            $settings = DistribusiSetting::where('kode_owner', $kode_owner)->get()->keyBy('role');
            if ($settings->isEmpty() || round($settings->sum('persentase'), 2) != 100) {
                 return response()->json(['success' => false, 'message' => 'Pengaturan persentase distribusi belum lengkap atau totalnya bukan 100%.'], 400);
            }

            DB::beginTransaction();
            try {
                $alokasi = [];
                 foreach ($settings as $role => $setting) {
                    $alokasi[$role] = $labaBersih * ($setting->persentase / 100);
                }

                $distribusi = DistribusiLaba::create([
                    'laba_kotor' => $labaResult['laba_kotor'], 'laba_bersih' => $labaBersih,
                    'alokasi_owner' => $alokasi['owner'] ?? 0, 'alokasi_investor' => $alokasi['investor'] ?? 0,
                    'alokasi_karyawan' => $alokasi['karyawan_bonus'] ?? 0, 'alokasi_kas_aset' => $alokasi['kas_aset'] ?? 0,
                    'kode_owner' => $kode_owner, 'tanggal' => now(),
                    'tanggal_mulai' => $tgl_mulai, 'tanggal_selesai' => $tgl_selesai,
                ]);

                foreach ($alokasi as $role => $jumlah) {
                     if ($jumlah > 0) {
                        AlokasiLaba::create([
                            'distribusi_laba_id' => $distribusi->id,
                            'kode_owner' => $kode_owner,
                            'user_id' => null, // Sederhanakan untuk API
                            'role' => $role,
                            'jumlah' => $jumlah,
                        ]);
                    }
                }

                DB::commit();
                return response()->json(['success' => true, 'message' => 'Distribusi laba berhasil diproses dan dicatat.']);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('API Process Profit Distribution Error', ['error' => $e->getMessage()]);
                return response()->json(['success' => false, 'message' => 'Gagal memproses distribusi: ' . $e->getMessage()], 500);
            }

        } catch (\Exception $e) {
            Log::error('API Process Profit Distribution Outer Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function getAllocationBalances(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $saldoTersedia = AlokasiLaba::where('kode_owner', $kode_owner)
                ->where('status', 'dialokasikan')
                ->select('role', DB::raw('SUM(jumlah) as total'))
                ->groupBy('role')
                ->pluck('total', 'role');

            // Definisikan semua role untuk memastikan semua muncul di response
            $roles = ['owner' => 0, 'investor' => 0, 'karyawan_bonus' => 0, 'kas_aset' => 0];
            $data = collect($roles)->merge($saldoTersedia);

            return response()->json([
                'success' => true,
                'message' => 'Saldo alokasi berhasil diambil',
                'data' => $data
            ]);

        } catch (\Exception $e) {
            Log::error('API Get Allocation Balances Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Gagal mengambil saldo alokasi.'], 500);
        }
    }

    public function processAllocationWithdrawal(Request $request)
    {
        $request->validate([
            'role' => 'required|string|in:owner,investor,karyawan_bonus,kas_aset',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $kode_owner = $this->getThisUser()->id_upline;
        $role = $request->role;
        $jumlahPenarikan = $request->jumlah;

        $saldoTersedia = AlokasiLaba::where('kode_owner', $kode_owner)
            ->where('role', $role)
            ->where('status', 'dialokasikan')
            ->sum('jumlah');

        if ($jumlahPenarikan > $saldoTersedia) {
            return response()->json(['success' => false, 'message' => 'Jumlah penarikan melebihi saldo yang tersedia.'], 422);
        }

        DB::beginTransaction();
        try {
            $alokasiTersedia = AlokasiLaba::where('kode_owner', $kode_owner)
                ->where('role', $role)
                ->where('status', 'dialokasikan')
                ->orderBy('created_at', 'asc')->get();

            $sisaUntukDitarik = $jumlahPenarikan;
            foreach ($alokasiTersedia as $alokasi) {
                if ($sisaUntukDitarik <= 0) break;

                $bisaDiambilDariAlokasiIni = min($alokasi->jumlah, $sisaUntukDitarik);

                // Catat pengeluaran di kas perusahaan (buku besar)
                $this->catatKas(
                    $alokasi, 0, $bisaDiambilDariAlokasiIni,
                    'Pencairan Laba: ' . Str::title(str_replace('_', ' ', $alokasi->role)) . ($request->keterangan ? ' - ' . $request->keterangan : ''),
                    now()
                );

                // Jika seluruh alokasi ini habis, ubah statusnya
                if ($bisaDiambilDariAlokasiIni >= $alokasi->jumlah) {
                    $alokasi->update(['status' => 'ditarik']);
                } else {
                    // Jika hanya sebagian, kurangi jumlahnya
                    $alokasi->decrement('jumlah', $bisaDiambilDariAlokasiIni);
                }

                $sisaUntukDitarik -= $bisaDiambilDariAlokasiIni;
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Dana berhasil dicairkan.']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('API Process Allocation Withdrawal Error', ['error' => $e->getMessage()]);
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }
}
