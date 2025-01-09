<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Laci;
use App\Models\KategoriLaci;
use App\Models\HistoryLaci;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LaciController extends Controller
{
    public function form(Request $request)
    {
        // Pengecekan apakah pengguna sudah login
        if (!Auth::check()) {
            return redirect()->route('home'); // Alihkan ke halaman home jika belum login
        }
        $this_user = Auth::user(); // Ambil data pengguna yang sedang login

        // Pengecekan jabatan
        // if ($this_user->jabatan = '1') {
        //     return redirect()->route('dashboard'); // Alihkan ke halaman home jika jabatan tidak sesuai
        // }

        $page = 'laci';
        $today = date('Y-m-d');

        if (isset($request->tgl_awal) && isset($request->tgl_akhir)) {
            $listLaci = HistoryLaci::join('kategori_lacis', 'history_laci.id_kategori', '=', 'kategori_lacis.id')
                ->select(
                    'kategori_lacis.id',
                    'kategori_lacis.name_laci',
                    DB::raw('COALESCE(SUM(history_laci.masuk), 0) as total_uang_masuk'),
                    DB::raw('COALESCE(SUM(history_laci.keluar), 0) as total_uang_keluar')
                )
                ->where([
                    ['kategori_lacis.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['history_laci.updated_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['history_laci.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']
                ])
                ->groupBy('kategori_lacis.id', 'kategori_lacis.name_laci')
                ->get();

            $riwayat = HistoryLaci::join('kategori_lacis', 'history_laci.id_kategori', '=', 'kategori_lacis.id')
                ->select(
                    'history_laci.*',
                    'kategori_lacis.name_laci'
                )
                ->where([
                    ['kategori_lacis.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['history_laci.updated_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['history_laci.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']
                ])
                ->groupBy(
                    'history_laci.id',
                    'history_laci.id_kategori',
                    'history_laci.kode_owner',
                    'history_laci.masuk',
                    'history_laci.keluar',
                    'history_laci.keterangan',
                    'history_laci.created_at',
                    'history_laci.updated_at',
                    'kategori_lacis.name_laci'
                )
                ->get();

            $komisi = DB::table('profit_presentases')
                ->join('sevices', 'sevices.id', '=', 'profit_presentases.kode_service') // Menggabungkan tabel sevices dan profit_presentases
                ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                ->select(
                    'profit_presentases.profit', // Kolom profit dari profit_presentases
                    'sevices.*', // Semua kolom dari sevices
                    'users.name'
                )
                ->where([
                    ['sevices.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['profit_presentases.updated_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['profit_presentases.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']
                ])
                ->get();
            $penarikan = DB::table('penarikans')
                ->join('users', 'users.id', '=', 'penarikans.kode_user')
                ->join('user_details', 'user_details.kode_user', '=', 'penarikans.kode_user')
                ->select(
                    'penarikans.*',
                    'users.name',
                    'user_details.saldo'
                )
                ->where([
                    ['penarikans.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['penarikans.updated_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['penarikans.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']
                ])
                ->get();
        } else {
            $listLaci = HistoryLaci::join('kategori_lacis', 'history_laci.id_kategori', '=', 'kategori_lacis.id')
                ->select(
                    'kategori_lacis.id',
                    'kategori_lacis.name_laci',
                    DB::raw('COALESCE(SUM(history_laci.masuk), 0) as total_uang_masuk'),
                    DB::raw('COALESCE(SUM(history_laci.keluar), 0) as total_uang_keluar')
                )
                ->where([
                    ['kategori_lacis.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['history_laci.updated_at', '>=', $today . ' 00:00:00'],
                    ['history_laci.updated_at', '<=', $today . ' 23:59:59']
                ])
                ->groupBy('kategori_lacis.id', 'kategori_lacis.name_laci')
                ->get();

            $riwayat = HistoryLaci::join('kategori_lacis', 'history_laci.id_kategori', '=', 'kategori_lacis.id')
                ->select(
                    'history_laci.*',
                    'kategori_lacis.name_laci'
                )
                ->where([
                    ['kategori_lacis.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['history_laci.updated_at', '>=', $today . ' 00:00:00'],
                    ['history_laci.updated_at', '<=', $today . ' 23:59:59']
                ])
                ->groupBy(
                    'history_laci.id',
                    'history_laci.id_kategori',
                    'history_laci.kode_owner',
                    'history_laci.masuk',
                    'history_laci.keluar',
                    'history_laci.keterangan',
                    'history_laci.created_at',
                    'history_laci.updated_at',
                    'kategori_lacis.name_laci'
                )
                ->get();
            $komisi = DB::table('profit_presentases')
                ->join('sevices', 'sevices.id', '=', 'profit_presentases.kode_service') // Menggabungkan tabel sevices dan profit_presentases
                ->join('user_details', 'sevices.id_teknisi', '=', 'user_details.kode_user')
                ->select(
                    'profit_presentases.profit', // Kolom profit dari profit_presentases
                    'sevices.*', // Semua kolom dari sevices
                    'user_details.fullname',
                    'profit_presentases.saldo'
                )
                ->where([
                    ['sevices.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['profit_presentases.updated_at', '>=', $today . ' 00:00:00'],
                    ['profit_presentases.updated_at', '<=', $today . ' 23:59:59']
                ])
                ->get();
            $penarikan = DB::table('penarikans')
                ->join('users', 'users.id', '=', 'penarikans.kode_user')
                ->join('user_details', 'user_details.kode_user', '=', 'penarikans.kode_user')
                ->select(
                    'penarikans.*',
                    'users.name',
                    'user_details.saldo'
                )
                ->where([
                    ['penarikans.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['penarikans.updated_at', '>=', $today . ' 00:00:00'],
                    ['penarikans.updated_at', '<=', $today . ' 23:59:59']
                ])
                ->get();
        }

        // Ambil semua kategori laci untuk memastikan semua ditampilkan
        $allLaci = KategoriLaci::where('kode_owner', $this->getThisUser()->id_upline)->get();

        // Jika history kosong, buat array untuk menyimpan kategori dan set uang masuk dan keluar ke 0
        if ($listLaci->isEmpty()) {
            $listLaci = $allLaci->map(function ($kategori) {
                return [
                    'id' => $kategori->id,
                    'name_laci' => $kategori->name_laci,
                    'total_uang_masuk' => 0,
                    'total_uang_keluar' => 0,
                ];
            });
        } else {
            // Jika ada data, pastikan untuk mengkonversi ke array untuk view
            $listLaci = $listLaci->toArray();
        }

        return view('laci.form', compact('page', 'listLaci', 'allLaci', 'riwayat', 'komisi', 'penarikan'));
    }


    public function store(Request $request)
    {
        $request->validate([
            'receh' => 'required|numeric|min:1',
            'real' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        // Cek jika sudah ada entri pada hari ini
        $laciEntry = Laci::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereDate('tanggal', $today)
            ->first();

        if ($laciEntry) {
            // Update data jika entri sudah ada
            $laciEntry->update([
                'receh' => $request->input('receh'),
                'real' => $request->input('real'),
            ]);
        } else {
            // Simpan data baru jika entri belum ada
            Laci::create([
                'user_id' => $user->id,
                'kode_owner' => $this->getThisUser()->id_upline,
                'receh' => $request->input('receh'),
                'real' => $request->input('real'),
                'tanggal' => $today,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Laci berhasil diisi atau diperbarui untuk hari ini.');
    }

    public function updatereal(Request $request)
    {
        $request->validate([
            'real' => 'required|numeric',
        ]);


        $today = Carbon::today()->format('Y-m-d');

        // Cek jika data Laci dengan kode_owner dan tanggal hari ini ada
        $laciEntry = Laci::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereDate('tanggal', $today)
            ->first();

        if ($laciEntry) {
            // Pastikan data yang diupdate benar-benar milik kode_owner yang sedang login dan tanggal hari ini

            $laciEntry->update([
                'real' => $request->input('real'),
            ]);
            return redirect()->route('dashboard')->with('success', 'Data Uang berhasil diperbarui.');
        } else {
            // Jika tidak cocok, jangan update dan bisa kirimkan error atau log
            return redirect()->back()->with('error', 'Aksi tidak diizinkan.');
        }
    }

    public function kategori_laci(Request $request)
    {
        $request->validate([
            'name_laci' => 'required|string',
        ]);

        // Cek jika sudah ada entri dengan nama laci yang sama
        $laciEntry = KategoriLaci::where('kode_owner', $this->getThisUser()->id_upline)
            ->where('name_laci', $request->input('name_laci'))
            ->first();

        if ($laciEntry) {
            // Update data jika entri sudah ada
            return redirect()->back()->with('error', 'Kategori sudah ada');
        } else {
            try {
                // Simpan data baru jika entri belum ada
                KategoriLaci::create([
                    'kode_owner' => $this->getThisUser()->id_upline,
                    'name_laci' => $request->input('name_laci'),
                ]);
                return redirect()->back()->with('success', 'Kategori Laci berhasil disimpan.');
            } catch (\Exception $e) {
                // Jika terjadi kesalahan saat menyimpan
                return redirect()->back()->with('error', 'Gagal menyimpan kategori laci: ' . $e->getMessage());
            }
        }
    }

    public function deleteKategoriLaci(Request $request)
    {
        $request->validate([
            'id_kategorilaci' => 'required|exists:kategori_lacis,id', // Pastikan id ada di database
        ]);

        $kategoriLaci = KategoriLaci::findOrFail($request->id_kategorilaci);
        $kategoriLaci->delete();

        return redirect()->back()->with('success', 'Kategori Laci berhasil dihapus.');
    }
}
