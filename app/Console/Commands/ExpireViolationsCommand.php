<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Violation;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class ExpireViolationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'violations:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check and mark violations as expired if they are older than 14 days';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $this->info("Checking for expired violations...");
        
        $cutoffDate = Carbon::now()->subDays(14)->toDateString();

        $expiredViolations = Violation::where('status', 'processed')
            ->whereDate('violation_date', '<', $cutoffDate)
            ->get();

        $count = 0;
        foreach ($expiredViolations as $violation) {
            $violation->status = 'expired';
            // Kita juga bisa mengisi reversed_at jika dibutuhkan, tapi untuk membedakan dengan forgiven lebih baik dibiarkan
            $violation->save();
            $count++;
        }

        $this->info("Successfully expired {$count} violations.");
        Log::info("Expired {$count} violations older than {$cutoffDate}.");

        return Command::SUCCESS;
    }
}

