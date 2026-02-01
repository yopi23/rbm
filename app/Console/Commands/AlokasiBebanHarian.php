<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\BebanOperasional;
use Illuminate\Support\Facades\DB;

class AlokasiBebanHarian extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:alokasi-beban-harian';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mengalokasikan dana beban operasional harian (Sinking Fund)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Memulai alokasi beban operasional harian...');

        $bebans = BebanOperasional::where('is_active', true)->get();

        foreach ($bebans as $beban) {
            $alokasiHarian = 0;

            if ($beban->periode == 'bulanan') {
                // Asumsi 30 hari atau daysInMonth?
                // Untuk sinking fund, agar pas di akhir bulan, lebih baik pakai daysInMonth
                // Tapi command ini jalan tiap hari.
                $daysInMonth = now()->daysInMonth;
                $alokasiHarian = $beban->nominal / $daysInMonth;
            } elseif ($beban->periode == 'tahunan') {
                $daysInYear = now()->daysInYear;
                $alokasiHarian = $beban->nominal / $daysInYear;
            }

            if ($alokasiHarian > 0) {
                // Update balance
                $beban->current_balance += $alokasiHarian;
                $beban->save();
                
                $this->line("Beban '{$beban->nama_beban}' dialokasikan: " . number_format($alokasiHarian, 2));
            }
        }

        $this->info('Alokasi beban harian selesai.');
        return 0;
    }
}
