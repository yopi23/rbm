<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penarikan;
use App\Models\UserDetail;
use App\Services\WhatsAppService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserDataController extends Controller
{
    public function getUserProfile($kode_user)
    {
        // Ambil saldo dari user_detail
        $saldo = DB::table('user_details')
            ->where('kode_user', $kode_user)
            ->value('saldo') ?? 0;

        // Hitung total penarikan dalam satu bulan dari tabel penarikan
        $total_penarikan = DB::table('penarikans')
            ->where('kode_user', $kode_user)
            ->whereMonth('tgl_penarikan', date('m'))
            ->whereYear('tgl_penarikan', date('Y'))
            ->sum('jumlah_penarikan');

        // Hitung total komisi dalam satu bulan dari tabel profit_presentases
        $total_komisi = DB::table('profit_presentases')
            ->where('kode_user', $kode_user)
            ->whereMonth('tgl_profit', date('m'))
            ->whereYear('tgl_profit', date('Y'))
            ->sum('profit');

        // Return data sebagai JSON
        return response()->json([
            'kode_user' => $kode_user,
            'saldo' => $saldo,
            'total_penarikan' => $total_penarikan,
            'total_komisi' => $total_komisi,
        ]);
    }
    public function getKaryawan()
    {
         // Mendapatkan user yang sedang login
        $currentUser = $this->getThisUser();

        // Cek apakah user ini adalah admin (misalnya jabatan 0 atau 1)
        if (in_array($currentUser->jabatan, [0, 1])) {
            // Ambil semua user di bawah upline yang sama, tapi exclude admin
            $karyawan = UserDetail::where('id_upline', $currentUser->id_upline)
                                ->whereNotIn('jabatan', [0, 1])
                                ->select('fullname','saldo','id','jabatan','id_upline')
                                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan ditemukan.',
                'data' => $karyawan
            ]);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak memiliki izin untuk mengakses data karyawan.',
                'data' => []
            ], 403);
        }
    }
    public function store_penarikan(Request $request)
    {
        $user = $this->getThisUser();
        $pegawais = UserDetail::where([['kode_user', '=', $user->kode_user]])->get()->first();
        // Validasi input
        $request->validate([
            'jumlah_penarikan' => 'required|numeric|min:1',
            'catatan_penarikan' => 'nullable|string|max:255',
        ]);

        $jumlahPenarikan = preg_replace('/[^0-9.]/', '', $request->jumlah_penarikan);

        // Cek saldo pengguna
        if ($pegawais->saldo < $jumlahPenarikan) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo tidak mencukupi'
            ], 400);
        }

        // Generate kode penarikan
        $kode = 'PEN' . date('Ymd') . $this->getThisUser()->id_upline . $this->getThisUser()->kode_user;

        // Simpan data penarikan
        $create = Penarikan::create([
            'tgl_penarikan' => date('Y-m-d h:i:s'),
            'kode_penarikan' => $kode,
            'kode_user' => $this->getThisUser()->kode_user,
            'kode_owner' => $user->id_upline,
            'jumlah_penarikan' => $jumlahPenarikan,
            'catatan_penarikan' => $request->catatan_penarikan ?? '-',
            'status_penarikan' => '1',
            'dari_saldo' => $user->saldo,
        ]);

        if ($create) {
            // Update saldo user
            $new_saldo = $user->saldo - $jumlahPenarikan;
            $pegawais->update(['saldo' => $new_saldo]);

            //wa send
    // Status WhatsApp notification
    $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak tersedia';

    // Dapatkan admin dari upline
    $admin = UserDetail::where([['kode_user', '=', $pegawais->id_upline]])->get()->first();

    // Array untuk menyimpan nomor telepon valid
    $validPhoneNumbers = [];

    // Inject WhatsAppService
    $whatsAppService = app(WhatsAppService::class);

    // Cek nomor admin
    if (!empty($admin->no_telp) && $whatsAppService->isValidPhoneNumber($admin->no_telp)) {
        $validPhoneNumbers[] = $admin->no_telp;
    }

    // Cek nomor owner/recipient kedua
    if (!empty($pegawais->no_telp) && $whatsAppService->isValidPhoneNumber($pegawais->no_telp)) {
        $validPhoneNumbers[] = $pegawais->no_telp;
    }

    // Kirim notifikasi WhatsApp jika ada nomor telepon valid
    if (count($validPhoneNumbers) > 0) {
        try {
            // Kirim notifikasi ke semua nomor valid sekaligus
            $waResult = $whatsAppService->penarikanNotification([
                'teknisi' => $pegawais->fullname,
                'jumlah' => 'Rp ' . number_format($jumlahPenarikan, 0, ',', '.'),
                'catatan' => $request->catatan_penarikan != null ? $request->catatan_penarikan : '-',
                'no_hp' => $validPhoneNumbers,
            ]);

            if ($waResult['status']) {
                $whatsappStatus = 'Pesan WhatsApp berhasil dikirim ke semua penerima';
            } else {
                // Cek apakah sebagian berhasil
                $successCount = count(array_filter($waResult['details'], function($detail) {
                    return $detail['status'] === true;
                }));

                if ($successCount > 0) {
                    $whatsappStatus = "Pesan WhatsApp berhasil dikirim ke {$successCount} dari " . count($validPhoneNumbers) . " penerima";
                } else {
                    $whatsappStatus = 'Pesan WhatsApp gagal dikirim: ' . $waResult['message'];
                }
            }
        } catch (\Exception $waException) {
            // Log error tapi jangan batalkan transaksi utama
            \Log::error("Failed to send WhatsApp notification: " . $waException->getMessage(), [
                'penarikan' => $pegawais->fullname,
                'recipients' => $validPhoneNumbers,
                'exception' => $waException
            ]);

            $whatsappStatus = 'Pesan WhatsApp gagal dikirim: Terjadi kesalahan sistem';
        }
    } else {
        $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Tidak ada nomor telepon valid';
    }

                //end wa send

                return response()->json([
                    'status' => 'success',
                    'message' => 'Penarikan berhasil dibuat',
                    'data' => $create
                ], 201);

            }

            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan, coba lagi nanti'
            ], 500);
    }

    public function adminWithdrawEmployee(Request $request)
    {
        // Pastikan yang akses adalah admin
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. Hanya admin yang dapat melakukan penarikan saldo karyawan.'
            ], 403);
        }

        // Validasi input
        $request->validate([
            'kode_user' => 'required|numeric',
            'jumlah_penarikan' => 'required|numeric|min:1',
            'catatan_penarikan' => 'nullable|string|max:255',
        ]);

        $jumlahPenarikan = preg_replace('/[^0-9.]/', '', $request->jumlah_penarikan);
        $targetEmployee = UserDetail::where('kode_user', $request->kode_user)->first();

        if (!$targetEmployee) {
            return response()->json([
                'success' => false,
                'message' => 'Karyawan tidak ditemukan'
            ], 404);
        }

        // Generate kode penarikan khusus untuk admin
        $kode = 'ADM' . date('Ymd') . $user->kode_user . $request->kode_user;

        try {
            // Simpan data penarikan ke tabel penarikans
            $create = Penarikan::create([
                'tgl_penarikan' => date('Y-m-d H:i:s'),
                'kode_penarikan' => $kode,
                'kode_user' => $request->kode_user,
                'kode_owner' => $user->kode_user, // Admin yang melakukan penarikan
                'jumlah_penarikan' => $jumlahPenarikan,
                'catatan_penarikan' => $request->catatan_penarikan ?? "Penarikan oleh admin untuk {$targetEmployee->fullname}",
                'status_penarikan' => '1', // Langsung disetujui karena admin yang melakukan
                'dari_saldo' => $targetEmployee->saldo,
                'admin_withdrawal' => true, // Flag khusus untuk penarikan admin
                'admin_id' => $user->kode_user, // ID admin yang melakukan penarikan
            ]);

            if ($create) {
                // Update saldo karyawan (bisa minus)
                $newSaldo = $targetEmployee->saldo - $jumlahPenarikan;
                $targetEmployee->update(['saldo' => $newSaldo]);

                // Log aktivitas admin
                \Log::info("Admin withdrawal", [
                    'admin_id' => $user->kode_user,
                    'admin_name' => $user->fullname,
                    'employee_id' => $request->kode_user,
                    'employee_name' => $targetEmployee->fullname,
                    'amount' => $jumlahPenarikan,
                    'old_balance' => $targetEmployee->saldo,
                    'new_balance' => $newSaldo,
                    'note' => $request->catatan_penarikan,
                    'withdrawal_code' => $kode
                ]);

                return response()->json([
                    'success' => true,
                    'message' => "Penarikan berhasil untuk {$targetEmployee->fullname}",
                    'data' => [
                        'kode_penarikan' => $kode,
                        'employee_name' => $targetEmployee->fullname,
                        'amount' => $jumlahPenarikan,
                        'old_balance' => $targetEmployee->saldo + $jumlahPenarikan,
                        'new_balance' => $newSaldo,
                        'admin_name' => $user->fullname
                    ]
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data penarikan'
            ], 500);

        } catch (\Exception $e) {
            \Log::error("Admin withdrawal error", [
                'error' => $e->getMessage(),
                'admin_id' => $user->kode_user,
                'employee_id' => $request->kode_user,
                'amount' => $jumlahPenarikan
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses penarikan: ' . $e->getMessage()
            ], 500);
        }
    }

    // Tambahkan juga method untuk mendapatkan history penarikan admin
    public function adminWithdrawalHistory(Request $request)
    {
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $query = Penarikan::with(['employee:kode_user,fullname', 'admin:kode_user,fullname'])
            ->where('admin_withdrawal', true)
            ->orderBy('created_at', 'desc');

        // Filter by date range if provided
        if ($request->has('start_date') && $request->has('end_date')) {
            $query->whereBetween('tgl_penarikan', [
                $request->start_date . ' 00:00:00',
                $request->end_date . ' 23:59:59'
            ]);
        }

        // Filter by employee if provided
        if ($request->has('kode_user')) {
            $query->where('kode_user', $request->kode_user);
        }

        $withdrawals = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $withdrawals
        ]);
    }

    // Method untuk mendapatkan ringkasan penarikan admin
    public function adminWithdrawalSummary()
    {
        $user = $this->getThisUser();
        if ($user->jabatan != '1') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            $today = date('Y-m-d');
            $thisMonth = date('Y-m');

            $summary = [
                'today' => [
                    'count' => Penarikan::where('admin_withdrawal', true)
                        ->whereDate('tgl_penarikan', $today)
                        ->count(),
                    'total' => Penarikan::where('admin_withdrawal', true)
                        ->whereDate('tgl_penarikan', $today)
                        ->sum('jumlah_penarikan')
                ],
                'this_month' => [
                    'count' => Penarikan::where('admin_withdrawal', true)
                        ->where('tgl_penarikan', 'like', $thisMonth . '%')
                        ->count(),
                    'total' => Penarikan::where('admin_withdrawal', true)
                        ->where('tgl_penarikan', 'like', $thisMonth . '%')
                        ->sum('jumlah_penarikan')
                ],
                'all_time' => [
                    'count' => Penarikan::where('admin_withdrawal', true)->count(),
                    'total' => Penarikan::where('admin_withdrawal', true)->sum('jumlah_penarikan')
                ]
            ];

            return response()->json([
                'success' => true,
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ], 500);
        }
    }
}
