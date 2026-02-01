<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\TransaksiModal;
use App\Models\KasPerusahaan;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ambil semua transaksi modal yang belum tercatat di kas_perusahaan
        $transaksis = TransaksiModal::doesntHave('kas')->orderBy('tanggal')->get();

        foreach ($transaksis as $transaksi) {
            DB::transaction(function () use ($transaksi) {
                // Ambil saldo terakhir untuk owner yang bersangkutan
                $saldoTerakhir = KasPerusahaan::where('kode_owner', $transaksi->kode_owner)
                    ->latest('id')
                    ->lockForUpdate()
                    ->value('saldo') ?? 0;

                $debit = 0;
                $kredit = 0;
                $deskripsi = '';

                if (in_array($transaksi->jenis_transaksi, ['setoran_awal', 'tambahan_modal'])) {
                    $debit = $transaksi->jumlah;
                    $deskripsi = $transaksi->jenis_transaksi == 'setoran_awal' ? 'Setoran Modal Awal' : 'Tambahan Modal Usaha';
                } else { // penarikan_modal
                    $kredit = $transaksi->jumlah;
                    $deskripsi = 'Penarikan Modal (Prive)';
                }
                
                if ($transaksi->keterangan) {
                    $deskripsi .= ' - ' . $transaksi->keterangan;
                }

                $saldoBaru = $saldoTerakhir + $debit - $kredit;

                // Buat entri kas baru
                KasPerusahaan::create([
                    'kode_owner' => $transaksi->kode_owner,
                    'tanggal' => $transaksi->tanggal,
                    'deskripsi' => $deskripsi,
                    'debit' => $debit,
                    'kredit' => $kredit,
                    'saldo' => $saldoBaru,
                    'sourceable_id' => $transaksi->id,
                    'sourceable_type' => TransaksiModal::class,
                ]);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus entri kas yang bersumber dari TransaksiModal
        // Hati-hati: ini akan menghapus semua kas dari modal, bukan hanya yang dibuat oleh migrasi ini.
        // Namun, karena ini backfill, asumsinya kita ingin mengembalikan ke kondisi semula (tidak ada kas dari modal).
        // Tapi jika ada transaksi lain setelahnya yang bergantung pada saldo ini, saldonya akan kacau.
        // Sebaiknya down() dibiarkan kosong atau logic penghapusan yang sangat spesifik.
        // Untuk keamanan data, kita kosongkan saja.
    }
};
