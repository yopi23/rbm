<?php
namespace App\Console\Commands;
use Illuminate\Console\Command;
use App\Models\Aset;
use Carbon\Carbon;

class HitungPenyusutanBulanan extends Command
{
    protected $signature = 'app:hitung-penyusutan-bulanan';
    protected $description = 'Menghitung dan mencatat penyusutan bulanan untuk semua aset tetap';

    public function handle()
    {
        $this->info('Memulai perhitungan penyusutan bulanan...');
        $asets = Aset::where('nilai_buku', '>', DB::raw('nilai_residu'))->get();

        foreach ($asets as $aset) {
            // Biaya penyusutan per bulan
            $penyusutanPerBulan = ($aset->nilai_perolehan - $aset->nilai_residu) / $aset->masa_manfaat_bulan;

            // Pastikan tidak menyusut melebihi nilai residu
            if ($aset->penyusutan_terakumulasi + $penyusutanPerBulan >= ($aset->nilai_perolehan - $aset->nilai_residu)) {
                $penyusutanPerBulan = ($aset->nilai_perolehan - $aset->nilai_residu) - $aset->penyusutan_terakumulasi;
            }

            if ($penyusutanPerBulan > 0) {
                $aset->penyusutan_terakumulasi += $penyusutanPerBulan;
                $aset->nilai_buku = $aset->nilai_perolehan - $aset->penyusutan_terakumulasi;
                $aset->save();

                $this->line("Aset '{$aset->nama_aset}' disusutkan sebesar: " . number_format($penyusutanPerBulan));
            }
        }
        $this->info('Perhitungan penyusutan bulanan selesai.');
        return 0;
    }
}
