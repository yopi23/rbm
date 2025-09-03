<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BebanOperasional;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
        $beban = BebanOperasional::where('kode_owner', $this->getOwnerId())->get();

        $content = view('admin.page.beban.index', compact('page', 'beban'));
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
