<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BebanOperasional;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class BebanOperasionalController extends Controller
{
    private function getOwnerId(): int
    {
        $user = Auth::user();
        return ($user->userDetail->jabatan == '1') ? $user->id : $user->userDetail->id_upline;
    }

    /**
     * Menampilkan halaman untuk mengelola beban tetap bulanan.
     */
    public function index()
    {
        $page = "Kelola Beban Tetap"; // Judul diubah sedikit
        $ownerId = $this->getOwnerId();

        $namaBulan = Carbon::now()->locale('id')->isoFormat('MMMM YYYY');

        // Ambil semua beban, eager load semua pengeluaran dari awal tahun ini untuk efisiensi
        $awalTahunIni = Carbon::now()->startOfYear();
        $beban = BebanOperasional::where('kode_owner', $ownerId)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalTahunIni) {
                $query->where('tgl_pengeluaran', '>=', $awalTahunIni);
            }])
            ->orderBy('periode', 'desc') // Tampilkan tahunan dulu
            ->orderBy('nama_beban', 'asc')
            ->get();

        // Siapkan variabel untuk tanggal bulan ini
        $awalBulanIni = Carbon::now()->startOfMonth();
        $akhirBulanIni = Carbon::now()->endOfMonth();

        $beban->map(function ($item) use ($awalBulanIni, $akhirBulanIni) {

            // Tentukan pengeluaran yang relevan berdasarkan periode
            if ($item->periode == 'tahunan') {
                // Untuk tahunan, semua pengeluaran yang di-load relevan
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional;
            } else { // Default ke 'bulanan'
                // Untuk bulanan, filter lagi dari data yang sudah di-load
                $pengeluaranPeriodeIni = $item->pengeluaranOperasional->whereBetween('tgl_pengeluaran', [$awalBulanIni, $akhirBulanIni]);
            }

            // Hitung properti baru
            $item->terpakai_periode_ini = $pengeluaranPeriodeIni->sum('jml_pengeluaran');
            $item->sisa_jatah = $item->nominal - $item->terpakai_periode_ini;

            // Hitung beban ekuivalen bulanan untuk kalkulasi total yang akurat
            $item->beban_ekuivalen_bulanan = ($item->periode == 'tahunan') ? $item->nominal / 12 : $item->nominal;

            return $item;
        });

        // Kalkulasi total berdasarkan beban ekuivalen bulanan
        $totalJatahBulanan = $beban->sum('beban_ekuivalen_bulanan');

        $content = view('admin.page.beban.index', compact(
            'page', 'beban', 'namaBulan', 'totalJatahBulanan'
        ));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }


    /**
     * Menyimpan beban tetap baru.
     */
    public function store(Request $request)
    {
        $request->validate([
            'nama_beban' => 'required|string|max:255',
            'periode' => 'required|in:bulanan,tahunan', // Validasi periode
            'nominal' => 'required|numeric|min:0', // Ganti dari jumlah_bulanan
            'keterangan' => 'nullable|string',
        ]);

        BebanOperasional::create($request->all() + ['kode_owner' => $this->getOwnerId()]);
        return redirect()->route('beban.index')->with('success', 'Beban tetap baru berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

        $request->validate([
            'nama_beban' => 'required|string|max:255',
            'periode' => 'required|in:bulanan,tahunan', // Validasi periode
            'nominal' => 'required|numeric|min:0', // Ganti dari jumlah_bulanan
        ]);

        $beban = BebanOperasional::where('kode_owner', $this->getOwnerId())->findOrFail($id);
        $beban->update($request->all());
        return redirect()->route('beban.index')->with('success', 'Data beban berhasil diperbarui.');
    }

    /**
     * Menghapus data beban tetap.
     */
    public function destroy($id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->back()->withErrors(['error' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.']);
        }

        $beban = BebanOperasional::where('kode_owner', $this->getOwnerId())->findOrFail($id);
        $beban->delete();
        return redirect()->route('beban.index')->with('success', 'Data beban berhasil dihapus.');
    }
}
