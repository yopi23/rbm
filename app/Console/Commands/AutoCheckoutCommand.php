<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Http\Controllers\Admin\AttendanceCronController;
use Illuminate\Support\Facades\Log;

class AutoCheckoutCommand extends Command
{
    protected $signature = 'attendance:auto-checkout';
    protected $description = 'Auto check out employees who forgot to check out at end of shift';

    public function handle()
    {
        $this->info('Starting auto checkout for employees...');
        $controller = new AttendanceCronController();
        $result = $controller->runAutoCheckout();

        if ($result['success']) {
            $this->info('Auto checkout completed: ' . ($result['message'] ?? 'OK'));
            if (isset($result['data'])) {
                $this->info('Checked out count: ' . ($result['data']['checked_out_count'] ?? 0));
            }
            return 0;
        } else {
            $this->error('Auto checkout failed: ' . ($result['message'] ?? 'Unknown error'));
            return 1;
        }
    }
}
