<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Shift;
use App\Models\StockHistory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AutoCloseShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:auto-close';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Automatically close open shifts at the end of the day';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info('Starting auto-close shifts process...');
        
        $openShifts = Shift::where('status', 'open')->get();

        if ($openShifts->isEmpty()) {
            $this->info('No open shifts found.');
            return 0;
        }

        $count = 0;

        foreach ($openShifts as $shift) {
            // Skip shifts that started less than 12 hours ago
            // This prevents auto-closing active night shifts if the cron runs at 03:00 AM
            $startTime = \Carbon\Carbon::parse($shift->start_time);
            if ($startTime > now()->subHours(12)) {
                $this->info("Skipping Shift ID {$shift->id} (Started < 12h ago).");
                continue;
            }

            try {
                DB::beginTransaction();

                // Calculate financials (Logic copied from ShiftApiController::close)
                // EXCLUDE Pembelian (Stock) and Hutang (Debt) -> Kas Toko
                $cashIn = $shift->kasPerusahaan()
                    ->whereNotIn('sourceable_type', ['App\Models\Pembelian', 'App\Models\Hutang'])
                    ->sum('debit');
                $cashOut = $shift->kasPerusahaan()
                    ->whereNotIn('sourceable_type', ['App\Models\Pembelian', 'App\Models\Hutang'])
                    ->sum('kredit');
                $expectedCash = $shift->modal_awal + $cashIn - $cashOut;

                // Generate Snapshot Report (Simplified version of ShiftApiController::getSparepartAnalysis)
                $sparepartReport = $this->getSparepartAnalysis($shift);
                
                $reportData = [
                    'cash_in' => $cashIn,
                    'cash_out' => $cashOut,
                    'expected_cash' => $expectedCash,
                    'sparepart_analysis' => $sparepartReport,
                    'closed_at' => now()->toDateTimeString(),
                    'closed_by' => 'SYSTEM'
                ];

                // Assume actual = expected for auto-close
                $saldoAkhirAktual = $expectedCash;
                $selisih = 0;

                $shift->update([
                    'end_time' => now(),
                    'saldo_akhir_sistem' => $expectedCash,
                    'saldo_akhir_aktual' => $saldoAkhirAktual,
                    'selisih' => $selisih,
                    'status' => 'closed',
                    'note' => 'Auto closed by system (Cron Job)',
                    'report_data' => json_encode($reportData),
                ]);

                DB::commit();
                $count++;
                $this->info("Shift ID {$shift->id} (User: {$shift->user_id}) closed successfully.");

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to auto-close shift ID {$shift->id}: " . $e->getMessage());
                $this->error("Failed to close shift ID {$shift->id}: " . $e->getMessage());
            }
        }

        $this->info("Completed. {$count} shifts closed.");
        return 0;
    }

    /**
     * Helper for sparepart analysis (Reused from ShiftApiController logic)
     */
    private function getSparepartAnalysis($shift)
    {
        $data = [];
        $startTime = $shift->start_time;
        $endTime = now();
        $userId = $shift->user_id;

        $histories = StockHistory::with('sparepart')
            ->where(function($query) use ($shift, $userId, $startTime, $endTime) {
                $query->where('shift_id', $shift->id)
                      ->orWhere(function($q) use ($userId, $startTime, $endTime) {
                          $q->whereNull('shift_id')
                            ->where('user_input', $userId)
                            ->whereBetween('created_at', [$startTime, $endTime]);
                      });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($histories as $history) {
            $id = $history->sparepart_id;
            
            if (!$history->sparepart) continue;

            if (!isset($data[$id])) {
                $data[$id] = [
                    'nama' => $history->sparepart->nama_sparepart,
                    'used' => 0,
                    'stock_in' => 0,
                    'current_stock' => 0,
                    'initial_stock_est' => 0
                ];
            }

            if ($history->quantity_change < 0) {
                $data[$id]['used'] += abs($history->quantity_change);
            } else {
                $data[$id]['stock_in'] += $history->quantity_change;
            }

            $data[$id]['current_stock'] = $history->stock_after;
        }

        foreach($data as &$item) {
            $item['initial_stock_est'] = $item['current_stock'] + $item['used'] - $item['stock_in'];
            $item['sisa'] = $item['current_stock'];
        }

        return array_values($data);
    }
}
