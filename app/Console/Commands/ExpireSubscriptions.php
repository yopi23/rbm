<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ExpireSubscriptions extends Command
{
    protected $signature = 'subscriptions:expire';
    protected $description = 'Check for expired subscriptions and update their status';

    public function handle()
    {
        $expiredSubscriptions = Subscription::where('status', 'active')
            ->where('expires_at', '<=', now())
            ->get();

        if ($expiredSubscriptions->isEmpty()) {
            $this->info('Tidak ada langganan yang kedaluwarsa.');
            return 0;
        }

        foreach ($expiredSubscriptions as $subscription) {
            $subscription->update(['status' => 'expired']);

            // Buat Log
            (new \App\Services\SubscriptionService())->createLog(
                $subscription,
                'EXPIRED',
                'Langganan otomatis berakhir sesuai tanggal.',
                null // Dilakukan oleh sistem
            );
            $this->info("Langganan untuk user ID: {$subscription->user_id} telah berakhir.");
        }

        Log::info("{$expiredSubscriptions->count()} langganan telah diubah statusnya menjadi expired.");
        $this->info('Proses pengecekan langganan kedaluwarsa selesai.');
        return 0;
    }
}
