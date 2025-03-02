<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penarikan;
use App\Models\UserDetail;
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

            return response()->json([
                'status' => 'success',
                'message' => 'Penarikan berhasil dibuat',
                'data' => $create
            ], 201);


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


        }

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan, coba lagi nanti'
        ], 500);
    }
}
