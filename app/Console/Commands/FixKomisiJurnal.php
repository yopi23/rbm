<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\JurnalHarianCabang;
use App\Models\Shift;
use App\Models\Pengambilan;
use App\Models\Sevices;
use App\Models\ProfitPresentase;

class FixKomisiJurnal extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:komisi-jurnal {--dry-run : Only show what would be updated}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix overly inflated komisi_teknisi in jurnal_harian_cabangs without affecting actual tech balances';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting komisi_teknisi recalculation for JurnalHarianCabang...');
        $jurnals = JurnalHarianCabang::whereNotNull('shift_id')->get();
        $isDryRun = $this->option('dry-run');

        $totalFixed = 0;

        foreach ($jurnals as $jurnal) {
            $shift = Shift::find($jurnal->shift_id);
            if (!$shift) continue;

            $pengambilanIds = Pengambilan::where('shift_id', $shift->id)->pluck('id');
            $services = Sevices::where(function ($q) use ($shift) {
                $q->where('shift_id', $shift->id)
                  ->where('status_services', 'Diambil');
            })
            ->orWhereIn('kode_pengambilan', $pengambilanIds)
            ->distinct()
            ->pluck('id');

            $correctKomisi = ProfitPresentase::whereIn('kode_service', $services)
                ->sum('profit');

            if ($jurnal->komisi_teknisi != $correctKomisi) {
                $oldVal = $jurnal->komisi_teknisi;
                if (!$isDryRun) {
                    $jurnal->komisi_teknisi = $correctKomisi;
                    $jurnal->save();
                }
                
                $this->line("Updated Jurnal ID {$jurnal->id} (Shift {$shift->id}) | Old: {$oldVal} -> New: {$correctKomisi}");
                $totalFixed++;
            }
        }

        if ($isDryRun) {
            $this->info("Dry run completed. {$totalFixed} records would be fixed.");
        } else {
            $this->info("Completed. {$totalFixed} records fixed.");
        }

        return 0;
    }
}
