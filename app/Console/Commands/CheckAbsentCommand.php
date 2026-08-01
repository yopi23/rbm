<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\AttendanceCronController;
use Illuminate\Support\Facades\Log;

class CheckAbsentCommand extends Command
{
    protected $signature = 'attendance:check-absent';
    protected $description = 'Check for absent employees, mark alpha, and generate violations if applicable';

    public function handle()
    {
        $this->info('Starting auto absent employee check...');
        $controller = new AttendanceCronController();
        $result = $controller->runCheckAbsent();

        if ($result['success']) {
            $this->info('Auto absent check completed: ' . ($result['message'] ?? 'OK'));
            if (isset($result['data'])) {
                $this->table(['Total Karyawan', 'Ditandai Alpha', 'Pelanggaran Dibuat'], [[
                    $result['data']['processed_count'] ?? 0,
                    $result['data']['alpha_count'] ?? 0,
                    $result['data']['violation_count'] ?? 0,
                ]]);

                if (!empty($result['data']['details'])) {
                    $this->info("\nRincian Hasil Pengecekan Karyawan:");
                    $tableRows = [];
                    foreach ($result['data']['details'] as $item) {
                        $tableRows[] = [
                            $item['user_id'],
                            $item['name'],
                            $item['status']
                        ];
                    }
                    $this->table(['ID User', 'Nama Karyawan', 'Status Pengecekan'], $tableRows);
                }
            }
            return 0;
        } else {
            $this->error('Auto absent check failed: ' . ($result['message'] ?? 'Unknown error'));
            return 1;
        }
    }
}
