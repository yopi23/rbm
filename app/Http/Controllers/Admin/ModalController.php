<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KasPerusahaan;
use App\Models\TransaksiModal;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ModalController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId()
    {
        $user = Auth::user();
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    public function index()
    {
        $page = "Manajemen Modal Usaha";
        $ownerId = $this->getOwnerId();
        $transaksiModal = TransaksiModal::where('kode_owner', $ownerId)
                            ->orderBy('tanggal', 'desc')
                            ->paginate(15);
        $content = view('admin.page.financial.modal.index', compact('page', 'transaksiModal'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function create()
    {
        $page = "Tambah Transaksi Modal";
        $content = view('admin.page.financial.modal.create', compact('page'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'jenis_transaksi' => 'required|in:setoran_awal,tambahan_modal,penarikan_modal',
            'jumlah' => 'required|numeric|min:1',
            'keterangan' => 'nullable|string|max:255',
        ]);

        $ownerId = $this->getOwnerId();
        DB::beginTransaction();
        try {
            $modal = TransaksiModal::create([
                'tanggal' => $request->tanggal, 'kode_owner' => $ownerId,
                'jenis_transaksi' => $request->jenis_transaksi,
                'jumlah' => $request->jumlah, 'keterangan' => $request->keterangan,
            ]);

            $debit = 0; $kredit = 0;
            if (in_array($request->jenis_transaksi, ['setoran_awal', 'tambahan_modal'])) {
                $debit = $request->jumlah;
                $deskripsi = $request->jenis_transaksi == 'setoran_awal' ? 'Setoran Modal Awal' : 'Tambahan Modal Usaha';
            } else { // penarikan_modal
                $kredit = $request->jumlah;
                $deskripsi = 'Penarikan Modal (Prive)';
            }
            if ($request->keterangan) $deskripsi .= ' - ' . $request->keterangan;

           $this->catatKas($modal, $debit, $kredit, $deskripsi, $request->tanggal);
            DB::commit();
            return redirect()->route('modal.index')->with('success', 'Transaksi modal berhasil dicatat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Menampilkan form untuk mengedit transaksi modal.
     */
    public function edit($id)
    {
        $page = "Edit Transaksi Modal";
        $transaksi = TransaksiModal::where('kode_owner', $this->getOwnerId())->findOrFail($id);

        $content = view('admin.page.financial.modal.edit', compact('page', 'transaksi'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Memperbarui data transaksi modal.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'tanggal' => 'required|date',
            'keterangan' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();
        try {
            $transaksi = TransaksiModal::where('kode_owner', $this->getOwnerId())->findOrFail($id);
            $transaksi->update($request->only('tanggal', 'keterangan'));

            // Update juga entri kas terkait
            if ($transaksi->kas) {
                $transaksi->kas->update(['tanggal' => $request->tanggal]);
            } else {
                // Jika belum ada di kas (misal data lama), buatkan sekarang
                $debit = 0; $kredit = 0;
                if (in_array($transaksi->jenis_transaksi, ['setoran_awal', 'tambahan_modal'])) {
                    $debit = $transaksi->jumlah;
                    $deskripsi = $transaksi->jenis_transaksi == 'setoran_awal' ? 'Setoran Modal Awal' : 'Tambahan Modal Usaha';
                } else { // penarikan_modal
                    $kredit = $transaksi->jumlah;
                    $deskripsi = 'Penarikan Modal (Prive)';
                }
                if ($transaksi->keterangan) $deskripsi .= ' - ' . $transaksi->keterangan;

                $this->catatKas($transaksi, $debit, $kredit, $deskripsi, $request->tanggal);
            }

            DB::commit();
            return redirect()->route('modal.index')->with('success', 'Transaksi modal berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Menghapus (membatalkan) transaksi modal.
     */
    public function destroy($id)
    {
        DB::beginTransaction();
        try {
            $transaksi = TransaksiModal::where('kode_owner', $this->getOwnerId())->findOrFail($id);

            // Buat entri kas pembalik (reversal)
            $debit = 0; $kredit = 0; $deskripsi = "Pembatalan: ";
            if (in_array($transaksi->jenis_transaksi, ['setoran_awal', 'tambahan_modal'])) {
                $kredit = $transaksi->jumlah; // Pemasukan dibalik menjadi pengeluaran
                $deskripsi .= "Setoran/Tambahan Modal";
            } else { // penarikan_modal
                $debit = $transaksi->jumlah; // Pengeluaran dibalik menjadi pemasukan
                $deskripsi .= "Penarikan Modal";
            }
            $deskripsi .= " (" . $transaksi->keterangan . ")";

            // Catat jurnal pembalik di kas
            // $this->catatKas($transaksi, $debit, $kredit, $deskripsi, now());

            // Hapus record modal aslinya
            $transaksi->delete();

            DB::commit();
            return redirect()->route('modal.index')->with('success', 'Transaksi modal berhasil dibatalkan dan jurnal baliknya telah dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan transaksi: ' . $e->getMessage());
        }
    }
}
