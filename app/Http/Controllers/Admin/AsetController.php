<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Aset;
use App\Models\Shift;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AsetController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId(): int
    {
        $user = Auth::user();
        return ($user->userDetail->jabatan == '1') ? $user->id : $user->userDetail->id_upline;
    }

    public function index()
    {
        $page = "Manajemen Aset Tetap";
        $asets = Aset::where('kode_owner', $this->getOwnerId())->orderBy('tanggal_perolehan', 'desc')->get();
        $content = view('admin.page.aset.index', compact('page', 'asets'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function create()
    {
        $page = "Tambah Aset Baru";
        $content = view('admin.page.aset.create', compact('page'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nama_aset' => 'required|string|max:255',
            'kategori_aset' => 'nullable|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'nilai_perolehan' => 'required|numeric|min:0',
            'keterangan' => 'nullable|string',
        ]);

        DB::beginTransaction();
        try {
            // Get Active Shift
            $shiftId = null;
            $activeShift = Shift::getActiveShift(Auth::id());
            if ($activeShift) {
                $shiftId = $activeShift->id;
            }

            // 1. Simpan data aset baru
            $aset = Aset::create([
                'kode_owner' => $this->getOwnerId(),
                'nama_aset' => $request->nama_aset,
                'kategori_aset' => $request->kategori_aset,
                'tanggal_perolehan' => $request->tanggal_perolehan,
                'nilai_perolehan' => $request->nilai_perolehan,
                'masa_manfaat_bulan' => $request->masa_manfaat_bulan ?? 48,
                'nilai_residu' => $request->nilai_residu ?? 0,
                'nilai_buku' => $request->nilai_perolehan, // Nilai buku awal = nilai perolehan
                'keterangan' => $request->keterangan,
                'shift_id' => $shiftId,
            ]);

            // 2. Catat pembelian aset sebagai pengeluaran di buku besar
            if ($request->jenis_perolehan == 'pembelian_baru') {
            $this->catatKas(
                $aset,
                0, // Debit
                $aset->nilai_perolehan, // Kredit
                'Pembelian Aset: ' . $aset->nama_aset,
                $aset->tanggal_perolehan
            );
        }

            DB::commit();
            return redirect()->route('asets.index')->with('success', 'Aset baru berhasil ditambahkan dan dicatat sebagai pengeluaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    public function edit(Aset $aset)
    {
        // Pastikan owner hanya bisa edit aset miliknya
        if ($aset->kode_owner != $this->getOwnerId()) {
            abort(403, 'Akses ditolak.');
        }

        $page = "Edit Aset";
        $content = view('admin.page.aset.edit', compact('page', 'aset'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function update(Request $request, Aset $aset)
    {
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return redirect()->back()->withErrors(['error' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.']);
        }

        if ($aset->kode_owner != $this->getOwnerId()) {
            abort(403);
        }

        $request->validate([
            'nama_aset' => 'required|string|max:255',
            'kategori_aset' => 'nullable|string|max:255',
            'tanggal_perolehan' => 'required|date',
            'keterangan' => 'nullable|string',
        ]);

        // Catatan: Nilai perolehan tidak diizinkan untuk diubah untuk menjaga integritas data kas.
        // Jika ada perubahan nilai, seharusnya dicatat sebagai transaksi terpisah (penjualan aset / revaluasi).
        $aset->update($request->except('nilai_perolehan'));

        return redirect()->route('asets.index')->with('success', 'Data aset berhasil diperbarui.');
    }

    public function destroy(Aset $aset)
    {
        $activeShift = Shift::getActiveShift(Auth::id());
        if (!$activeShift) {
            return redirect()->back()->withErrors(['error' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.']);
        }

        if ($aset->kode_owner != $this->getOwnerId()) {
            abort(403);
        }

        DB::beginTransaction();
        try {
            // Opsi: Jika aset dijual, catat sebagai pemasukan sebelum dihapus
            // Untuk simple delete, kita buat jurnal balik di kas
            $this->catatKas(
                $aset,
                $aset->nilai_perolehan, // Debit (Uang dianggap kembali)
                0, // Kredit
                'Pembatalan/Penjualan Aset: ' . $aset->nama_aset,
                now()
            );

            $aset->delete();
            DB::commit();

            return redirect()->route('asets.index')->with('success', 'Aset berhasil dihapus dan jurnal baliknya telah dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal menghapus aset: ' . $e->getMessage());
        }
    }
}
