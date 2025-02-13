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
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Terjadi kesalahan, coba lagi nanti'
        ], 500);
    }
}
