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
        // === ABSENSI & KARYAWAN ===
        // Cek karyawan Alpha & buat pelanggaran otomatis jam 08:30 pagi
        $schedule->command('attendance:check-absent')->dailyAt('08:30');

        // Auto Checkout karyawan yang belum checkout jam 16:30 sore
        $schedule->command('attendance:auto-checkout')->dailyAt('16:30');

        // === BACKUP DATABASE ===
        // Backup otomatis database MySQL ke storage/app/backups/ jam 02:00 pagi
        $schedule->command('app:backup-database')->dailyAt('02:00');

        // === LANGGANAN & SHIFT ===
        // Pengecekan langganan kedaluwarsa
        $schedule->command('subscriptions:expire')->daily();
        
        // Auto Close Shifts jam 03:00 pagi untuk menutup shift yang lupa ditutup kemarin
        $schedule->command('shift:auto-close')->dailyAt('03:00');
        
        // === KEUANGAN & MANAJEMEN ASET ===
        $schedule->command('assets:calculate-depreciation')->dailyAt('00:01');
        $schedule->command('app:alokasi-beban-harian')->dailyAt('00:05');
        $schedule->command('app:proses-tutup-buku-harian')->dailyAt('23:55');
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
