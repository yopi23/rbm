<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\KasPerusahaan;
use App\Models\Penjualan;
use App\Models\Sevices;
use App\Models\PengeluaranToko;
use App\Models\PengeluaranOperasional;
use App\Models\TransaksiModal;
use App\Models\Pembelian;
use Illuminate\Support\Facades\DB;

class BackfillLedger extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backfill-ledger {--recalculate-only : Hanya hitung ulang saldo tanpa insert data baru}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengisi ulang data KasPerusahaan dari transaksi yang hilang dan menghitung ulang saldo';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai proses backfill ledger...');

        if (!$this->option('recalculate-only')) {
            $this->backfillMissingTransactions();
        }

        $this->recalculateBalances();

        $this->info('Proses backfill dan hitung ulang selesai.');
        return 0;
    }

    private function backfillMissingTransactions()
    {
        DB::transaction(function () {
            // 1. Transaksi Modal
            $modals = TransaksiModal::doesntHave('kas')->get();
            foreach ($modals as $modal) {
                $debit = in_array($modal->jenis_transaksi, ['setoran_awal', 'tambahan_modal']) ? $modal->jumlah : 0;
                $kredit = $modal->jenis_transaksi == 'penarikan_modal' ? $modal->jumlah : 0;
                $deskripsi = $modal->jenis_transaksi == 'setoran_awal' ? 'Setoran Modal Awal' : 
                             ($modal->jenis_transaksi == 'tambahan_modal' ? 'Tambahan Modal Usaha' : 'Penarikan Modal');
                
                $this->createKasEntry($modal, $debit, $kredit, $deskripsi, $modal->tanggal);
                $this->line("Added Modal: {$modal->id}");
            }

            // 2. Penjualan (Hanya yang status 1 / Paid)
            $penjualans = Penjualan::where('status_penjualan', '1')->doesntHave('kas')->get();
            foreach ($penjualans as $jual) {
                $deskripsi = "Penjualan #" . $jual->no_faktur;
                $this->createKasEntry($jual, $jual->total_penjualan, 0, $deskripsi, $jual->updated_at); // Gunakan updated_at sebagai tanggal bayar
                $this->line("Added Penjualan: {$jual->id}");
            }

            // 3. Services (Hanya yang Diambil)
            $services = Sevices::where('status_services', 'Diambil')->doesntHave('kas')->get();
            foreach ($services as $srv) {
                $deskripsi = "Service #" . $srv->no_faktur;
                // Total biaya service
                $this->createKasEntry($srv, $srv->total_biaya, 0, $deskripsi, $srv->updated_at);
                $this->line("Added Service: {$srv->id}");
            }

            // 4. Pengeluaran Toko
            $pengeluarans = PengeluaranToko::doesntHave('kas')->get();
            foreach ($pengeluarans as $p) {
                $deskripsi = $p->nama_pengeluaran . " (" . $p->catatan_pengeluaran . ")";
                $this->createKasEntry($p, 0, $p->jumlah_pengeluaran, $deskripsi, $p->tanggal_pengeluaran);
                $this->line("Added Pengeluaran Toko: {$p->id}");
            }

            // 5. Pengeluaran Operasional
            $ops = PengeluaranOperasional::doesntHave('kas')->get();
            foreach ($ops as $op) {
                $deskripsi = $op->nama_pengeluaran . " - " . $op->desc_pengeluaran;
                $this->createKasEntry($op, 0, $op->jml_pengeluaran, $deskripsi, $op->tgl_pengeluaran);
                $this->line("Added Pengeluaran Ops: {$op->id}");
            }

            // 6. Pembelian (Stock)
            // Asumsi pembelian mengurangi kas saat dibuat
            $belis = Pembelian::doesntHave('kas')->get();
            foreach ($belis as $beli) {
                $deskripsi = "Pembelian Stok #" . $beli->no_faktur;
                $this->createKasEntry($beli, 0, $beli->total_beli, $deskripsi, $beli->created_at); // Atau tanggal faktur jika ada
                $this->line("Added Pembelian: {$beli->id}");
            }
        });
    }

    private function createKasEntry($model, $debit, $kredit, $deskripsi, $tanggal)
    {
        // Kita set saldo 0 dulu, nanti direcalculate
        $model->kas()->create([
            'kode_owner' => $model->kode_owner,
            'tanggal' => $tanggal,
            'deskripsi' => $deskripsi,
            'debit' => $debit,
            'kredit' => $kredit,
            'saldo' => 0, // Placeholder
        ]);
    }

    private function recalculateBalances()
    {
        $this->info('Menghitung ulang saldo berjalan...');
        
        // Group by Owner untuk perhitungan saldo yang benar per owner
        $owners = KasPerusahaan::select('kode_owner')->distinct()->pluck('kode_owner');

        foreach ($owners as $ownerId) {
            $runningBalance = 0;
            
            // Ambil semua transaksi owner ini, urutkan berdasarkan tanggal dan ID (untuk stabilitas urutan)
            $transactions = KasPerusahaan::where('kode_owner', $ownerId)
                ->orderBy('tanggal', 'asc')
                ->orderBy('created_at', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            foreach ($transactions as $trx) {
                $runningBalance += $trx->debit;
                $runningBalance -= $trx->kredit;
                
                // Update langsung via DB query untuk performa (hindari event eloquent jika ada)
                DB::table('kas_perusahaan')
                    ->where('id', $trx->id)
                    ->update(['saldo' => $runningBalance]);
            }
            
            $this->info("Saldo akhir Owner ID {$ownerId}: " . number_format($runningBalance));
        }
    }
}
