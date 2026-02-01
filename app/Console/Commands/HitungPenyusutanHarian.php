<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Aset;
use Illuminate\Support\Facades\DB;

class HitungPenyusutanHarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assets:calculate-depreciation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Menghitung penyusutan harian aset tetap';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai perhitungan penyusutan harian...');
        
        // Ambil aset yang nilai bukunya masih lebih besar dari nilai residu
        $asets = Aset::where('nilai_buku', '>', DB::raw('nilai_residu'))->get();

        foreach ($asets as $aset) {
            // Rumus: (Harga Perolehan - Nilai Residu) / (Masa Manfaat Bulan * 30 hari)
            // Asumsi 1 bulan = 30 hari untuk simplifikasi, atau gunakan hari kalender
            // Jika pakai daysInMonth, nilai penyusutan berfluktuasi tiap bulan.
            // Standard akuntansi sering pakai 30 hari atau 360/365 hari setahun.
            // Kita gunakan rata-rata 30.4 hari (365/12) atau simply bagi total hari.
            
            $totalHariManfaat = $aset->masa_manfaat_bulan * 30.4167; // Rata-rata hari per bulan
            
            if ($totalHariManfaat <= 0) continue;

            $totalDepreciableCost = $aset->nilai_perolehan - $aset->nilai_residu;
            $penyusutanHarian = $totalDepreciableCost / $totalHariManfaat;

            // Cek sisa yang bisa disusutkan
            $sisaPenyusutan = $totalDepreciableCost - $aset->penyusutan_terakumulasi;

            if ($sisaPenyusutan <= 0) {
                continue;
            }

            // Jika penyusutan harian lebih besar dari sisa, gunakan sisa
            if ($penyusutanHarian > $sisaPenyusutan) {
                $penyusutanHarian = $sisaPenyusutan;
            }

            // Update aset
            $aset->penyusutan_terakumulasi += $penyusutanHarian;
            $aset->nilai_buku = $aset->nilai_perolehan - $aset->penyusutan_terakumulasi;
            $aset->save();

            $this->line("Aset '{$aset->nama_aset}' disusutkan: " . number_format($penyusutanHarian, 2));
        }

        $this->info('Perhitungan penyusutan harian selesai.');
        return 0;
    }
}
