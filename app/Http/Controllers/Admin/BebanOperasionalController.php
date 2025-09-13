<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BebanOperasional;
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
        $page = "Kelola Beban Tetap Bulanan";
        $ownerId = $this->getOwnerId();

        // Tentukan rentang tanggal untuk bulan ini
        $awalBulan = Carbon::now()->startOfMonth();
        $akhirBulan = Carbon::now()->endOfMonth();

        // Mengambil nama bulan dan tahun saat ini (misal: "September 2025")
        $namaBulan = Carbon::now()->locale('id')->isoFormat('MMMM YYYY');

        $beban = BebanOperasional::where('kode_owner', $ownerId)
            ->with(['pengeluaranOperasional' => function ($query) use ($awalBulan, $akhirBulan) {
                $query->whereBetween('tgl_pengeluaran', [$awalBulan, $akhirBulan]);
            }])
            ->get();

        $beban->map(function ($item) {
            $item->terpakai_bulan_ini = $item->pengeluaranOperasional->sum('jml_pengeluaran');
            $item->sisa_jatah = $item->jumlah_bulanan - $item->terpakai_bulan_ini;
            return $item;
        });

        // 👇 TAMBAHKAN INI UNTUK MENGHITUNG TOTAL KESELURUHAN 👇
        $totalJatah = $beban->sum('jumlah_bulanan');
        $totalTerpakai = $beban->sum('terpakai_bulan_ini');
        $totalSisa = $beban->sum('sisa_jatah');

        $content = view('admin.page.beban.index', compact(
            'page',
            'beban',
            'namaBulan', // Kirim nama bulan ke view
            'totalJatah', // Kirim total ke view
            'totalTerpakai',
            'totalSisa'
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
            'jumlah_bulanan' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        BebanOperasional::create($request->all() + ['kode_owner' => $this->getOwnerId()]);

        return redirect()->route('beban.index')->with('success', 'Beban tetap baru berhasil ditambahkan.');
    }

    /**
     * Memperbarui data beban tetap.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'nama_beban' => 'required|string|max:255',
            'jumlah_bulanan' => 'required|numeric|min:0',
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
        $beban = BebanOperasional::where('kode_owner', $this->getOwnerId())->findOrFail($id);
        $beban->delete();

        return redirect()->route('beban.index')->with('success', 'Data beban berhasil dihapus.');
    }
}
