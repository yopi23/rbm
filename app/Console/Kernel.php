<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')->hourly();
        $schedule->command('subscriptions:expire')->daily();
        $schedule->command('app:proses-tutup-buku-harian')->dailyAt('23:55');
        
        // Auto Close Shifts (Runs with Auto Checkout)
        // Jalankan jam 3 pagi untuk menutup shift yang lupa ditutup kemarin
        $schedule->command('shift:auto-close')->dailyAt('03:00');
        
        // Financial & Asset Management
        $schedule->command('assets:calculate-depreciation')->dailyAt('00:01');
        $schedule->command('app:alokasi-beban-harian')->dailyAt('00:05');
    }

    /**
     * Register the commands for the application.
     *
     * @return void
     */
    protected function commands()
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');

    }
}
