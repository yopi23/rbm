<?php

namespace App\Observers;

use App\Models\PemasukkanLain;
use App\Models\KasPerusahaan;
use App\Models\Shift;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PemasukkanLainObserver
{
    /**
     * Handle the PemasukkanLain "created" event.
     * Catat pemasukan ke KasPerusahaan saat dibuat
     */
    public function created(PemasukkanLain $pemasukkanLain)
    {
        // Cek apakah sudah ada kas terkait (mencegah double recording)
        $existingKas = KasPerusahaan::where('sourceable_id', $pemasukkanLain->id)
            ->where('sourceable_type', PemasukkanLain::class)
            ->exists();

        if (!$existingKas) {
            $this->catatKas($pemasukkanLain);
        }
    }

    /**
     * Handle the PemasukkanLain "updated" event.
     * Update entry di KasPerusahaan jika jumlah berubah
     */
    public function updated(PemasukkanLain $pemasukkanLain)
    {
        // Jika jumlah berubah, update kas
        if ($pemasukkanLain->isDirty('jumlah_pemasukkan')) {
            $kas = KasPerusahaan::where('sourceable_id', $pemasukkanLain->id)
                ->where('sourceable_type', PemasukkanLain::class)
                ->latest('id')
                ->first();

            if ($kas) {
                $oldAmount = $pemasukkanLain->getOriginal('jumlah_pemasukkan');
                $newAmount = $pemasukkanLain->jumlah_pemasukkan;
                $selisih = $newAmount - $oldAmount;

                // Update nominal debit
                $kas->debit = $newAmount;
                // Update saldo (menambah jika selisih positif)
                $kas->saldo += $selisih;
                $kas->save();
            }
        }
    }

    /**
     * Handle the PemasukkanLain "deleted" event.
     * Hapus entry di KasPerusahaan jika pemasukan dihapus
     */
    public function deleted(PemasukkanLain $pemasukkanLain)
    {
        // Hapus dari Kas Perusahaan
        $kas = KasPerusahaan::where('sourceable_id', $pemasukkanLain->id)
            ->where('sourceable_type', PemasukkanLain::class)
            ->first();

        if ($kas) {
            $kas->delete();
        }
    }

    /**
     * Helper untuk mencatat kas
     * Pemasukan Lainnya adalah pemasukan (debit) yang langsung masuk ke laci
     */
    protected function catatKas($pemasukkanLain)
    {
        $ownerId = $pemasukkanLain->kode_owner;

        // Cek saldo terakhir
        $lastKas = KasPerusahaan::where('kode_owner', $ownerId)
            ->latest('id')
            ->first();
        $saldoTerakhir = $lastKas ? $lastKas->saldo : 0;

        $debit = $pemasukkanLain->jumlah_pemasukkan;
        $saldoBaru = $saldoTerakhir + $debit;

        // Ambil shift ID
        $shiftId = $pemasukkanLain->shift_id;
        if (!$shiftId && Auth::check()) {
            $activeShift = Shift::getActiveShift(Auth::id());
            if ($activeShift) {
                $shiftId = $activeShift->id;
            }
        }

        $tanggalKas = $pemasukkanLain->created_at ?? now();
        if (!empty($pemasukkanLain->tgl_pemasukkan)) {
            $parsedDate = \Carbon\Carbon::parse($pemasukkanLain->tgl_pemasukkan);
            if (!$parsedDate->isToday()) {
                $tanggalKas = $parsedDate->format('Y-m-d') . ' ' . $tanggalKas->format('H:i:s');
            }
        }

        KasPerusahaan::create([
            'kode_owner' => $ownerId,
            'tanggal' => $tanggalKas,
            'deskripsi' => $pemasukkanLain->judul_pemasukan . ($pemasukkanLain->catatan_pemasukkan ? ' - ' . $pemasukkanLain->catatan_pemasukkan : ''),
            'debit' => $debit,
            'kredit' => 0,
            'saldo' => $saldoBaru,
            'is_cash' => $pemasukkanLain->metode_bayar === 'cash',
            'shift_id' => $shiftId,
            'sourceable_id' => $pemasukkanLain->id,
            'sourceable_type' => PemasukkanLain::class ,
        ]);
    }
}
