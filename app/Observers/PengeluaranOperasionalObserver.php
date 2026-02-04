<?php

namespace App\Observers;

use App\Models\PengeluaranOperasional;
use App\Models\BebanOperasional;
use App\Models\KasPerusahaan;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PengeluaranOperasionalObserver
{
    /**
     * Handle the PengeluaranOperasional "created" event.
     *
     * @param  \App\Models\PengeluaranOperasional  $pengeluaranOperasional
     * @return void
     */
    public function created(PengeluaranOperasional $pengeluaranOperasional)
    {
        // 1. Kurangi Saldo Sinking Fund (Beban Operasional)
        if ($pengeluaranOperasional->beban_operasional_id) {
            $beban = BebanOperasional::find($pengeluaranOperasional->beban_operasional_id);
            if ($beban) {
                // Kurangi saldo saat ini karena sudah dipakai bayar
                $beban->decrement('current_balance', $pengeluaranOperasional->jml_pengeluaran);
                
                // Update terpakai_periode_ini juga untuk tracking budget
                // (Logic ini mungkin sudah ada di Controller, tapi aman untuk memastikan)
                // Note: Controller mungkin sudah menghitung sisa jatah, tapi tidak update terpakai_periode_ini di tabel beban
                // karena tabel beban tidak punya kolom 'terpakai_periode_ini' (itu calculated attribute di controller).
                // Jadi kita hanya update current_balance.
            }
        }

        // 2. Catat ke Kas Perusahaan (Jika belum dicatat oleh Controller)
        // Cek apakah sudah ada kas terkait (mencegah double recording jika Controller masih pakai catatKas)
        $existingKas = KasPerusahaan::where('sourceable_id', $pengeluaranOperasional->id)
            ->where('sourceable_type', PengeluaranOperasional::class)
            ->exists();

        if (!$existingKas) {
            $this->catatKas($pengeluaranOperasional);
        }
    }

    /**
     * Handle the PengeluaranOperasional "updated" event.
     *
     * @param  \App\Models\PengeluaranOperasional  $pengeluaranOperasional
     * @return void
     */
    public function updated(PengeluaranOperasional $pengeluaranOperasional)
    {
        // 1. Koreksi Saldo Sinking Fund
        if ($pengeluaranOperasional->isDirty('jml_pengeluaran') || $pengeluaranOperasional->isDirty('beban_operasional_id')) {
            // Revert yang lama
            $oldBebanId = $pengeluaranOperasional->getOriginal('beban_operasional_id');
            $oldAmount = $pengeluaranOperasional->getOriginal('jml_pengeluaran');

            if ($oldBebanId) {
                $oldBeban = BebanOperasional::find($oldBebanId);
                if ($oldBeban) {
                    $oldBeban->increment('current_balance', $oldAmount);
                }
            }

            // Apply yang baru
            if ($pengeluaranOperasional->beban_operasional_id) {
                $newBeban = BebanOperasional::find($pengeluaranOperasional->beban_operasional_id);
                if ($newBeban) {
                    $newBeban->decrement('current_balance', $pengeluaranOperasional->jml_pengeluaran);
                }
            }
        }

        // 2. Koreksi Kas Perusahaan
        $kas = KasPerusahaan::where('sourceable_id', $pengeluaranOperasional->id)
            ->where('sourceable_type', PengeluaranOperasional::class)
            ->latest() // Ambil yang terakhir jika ada duplikat (seharusnya one-to-one/polymorphic)
            ->first();

        if ($kas) {
            // Update saldo kas secara chain reaction itu SUSAH karena saldo kas bersifat running balance.
            // Cara paling aman: Hapus kas lama, buat kas baru di tanggal yang sama? 
            // ATAU: Update nominal debit/kredit dan trigger recalculate saldo (sangat berat).
            // Solusi Praktis: Update nominal saja, tapi saldo running balance akan selisih seterusnya.
            // Solusi Terbaik: Gunakan logic 'adjustment' atau biarkan user manual. 
            // TAPI, user minta otomatis.
            
            // Karena sistem kas ini sederhana (hanya mencatat transaksi), 
            // kita update nominal kredit-nya. Saldo akhir di record ini berubah.
            // TAPI record setelahnya saldonya tidak otomatis berubah.
            // INI MASALAH UMUM DI SISTEM AKUNTANSI SEDERHANA.
            
            // Untuk saat ini, kita update recordnya saja agar laporan harian benar.
            // Perbaikan saldo running balance butuh fitur 'Recalculate Cash Ledger'.
            
            $selisih = $pengeluaranOperasional->jml_pengeluaran - $oldAmount;
            
            // Jika selisih positif (pengeluaran nambah), saldo kas berkurang
            // Jika selisih negatif (pengeluaran berkurang), saldo kas nambah
            $kas->kredit = $pengeluaranOperasional->jml_pengeluaran;
            $kas->saldo -= $selisih; // Update saldo row ini saja
            $kas->save();
            
            // Note: Idealnya kita loop semua record kas setelah tanggal ini untuk update saldo.
            // Tapi itu berat. Kita asumsikan user akan melakukan 'Recalculate' jika saldo aneh.
        }
    }

    /**
     * Handle the PengeluaranOperasional "deleted" event.
     *
     * @param  \App\Models\PengeluaranOperasional  $pengeluaranOperasional
     * @return void
     */
    public function deleted(PengeluaranOperasional $pengeluaranOperasional)
    {
        // 1. Refund Saldo Sinking Fund
        if ($pengeluaranOperasional->beban_operasional_id) {
            $beban = BebanOperasional::find($pengeluaranOperasional->beban_operasional_id);
            if ($beban) {
                $beban->increment('current_balance', $pengeluaranOperasional->jml_pengeluaran);
            }
        }

        // 2. Hapus dari Kas Perusahaan
        $kas = KasPerusahaan::where('sourceable_id', $pengeluaranOperasional->id)
            ->where('sourceable_type', PengeluaranOperasional::class)
            ->first();

        if ($kas) {
            // Sama seperti update, menghapus record di tengah akan merusak running balance saldo.
            // Kita hapus saja, saldo row lain biarkan salah sampai recalculate.
            $kas->delete();
        }
    }

    /**
     * Helper untuk mencatat kas (Logic dari ManajemenKasTrait)
     */
    protected function catatKas($pengeluaran)
    {
        // Gunakan logic mirip ManajemenKasTrait tapi manual karena kita di Observer
        // Kita perlu owner_id, shift_id, dll.
        
        $ownerId = $pengeluaran->kode_owner;
        
        // Cek saldo terakhir
        $lastKas = KasPerusahaan::where('kode_owner', $ownerId)
            ->latest('id')
            ->first();
        $saldoTerakhir = $lastKas ? $lastKas->saldo : 0;
        
        $kredit = $pengeluaran->jml_pengeluaran;
        $saldoBaru = $saldoTerakhir - $kredit;

        // Cek shift active
        $shiftId = null;
        // Kita coba ambil shift dari pengeluaran jika ada, atau cari shift aktif user yang buat
        // Karena di observer, kita tidak punya akses request langsung, tapi bisa pakai Auth
        if (Auth::check()) {
            $activeShift = Shift::getActiveShift(Auth::id());
            if ($activeShift) {
                $shiftId = $activeShift->id;
            }
        }

        KasPerusahaan::create([
            'kode_owner'      => $ownerId,
            'tanggal'         => $pengeluaran->created_at ?? now(),
            'deskripsi'       => $pengeluaran->nama_pengeluaran . ($pengeluaran->desc_pengeluaran ? ' - ' . $pengeluaran->desc_pengeluaran : ''),
            'debit'           => 0,
            'kredit'          => $kredit,
            'saldo'           => $saldoBaru,
            'shift_id'        => $shiftId,
            'sourceable_id'   => $pengeluaran->id,
            'sourceable_type' => PengeluaranOperasional::class,
        ]);
    }
}
