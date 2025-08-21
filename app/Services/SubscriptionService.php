<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Subscription;
use App\Models\SubscriptionLog;
use App\Models\SubscriptionToken; // <-- Tambahkan ini
use App\Models\User; // <-- Tambahkan ini
use Carbon\Carbon;
use App\Events\PaymentVerified;

class SubscriptionService
{
    public function activateByPayment(Payment $payment)
    {
        $user = $payment->user;
        $plan = $payment->subscriptionPlan;

        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'activated_at' => now(),
                'expires_at' => $this->calculateExpiryDate($user, $plan->duration_in_months),
            ]
        );

        // Buat Log
        $this->createLog(
            $subscription,
            'EXTENDED_BY_PAYMENT',
            "Langganan diperpanjang/diaktifkan via QRIS Ref: {$payment->reference_code} untuk {$plan->duration_in_months} bulan."
        );
         // === KIRIM SINYAL CALLBACK DI SINI ===
        event(new PaymentVerified($payment));
    }

    /**
     * Mengaktifkan langganan menggunakan token.
     * @param User $user User yang mengaktifkan.
     * @param string $tokenString Kode token yang diinput.
     * @return Subscription Langganan yang baru diaktifkan/diperbarui.
     * @throws \Exception Jika token tidak valid.
     */
    public function activateByToken(User $user, string $tokenString): Subscription
    {
        // 1. Cari token di database yang belum terpakai
        $token = SubscriptionToken::where('token', $tokenString)
                                   ->where('is_used', false)
                                   ->first();

        // 2. Jika token tidak ada, lempar error
        if (!$token) {
            throw new \Exception('Token tidak valid atau sudah digunakan.');
        }

        // Ambil data paket dari token
        $plan = $token->plan;

        // 3. Buat atau perbarui langganan user
        $subscription = Subscription::updateOrCreate(
            ['user_id' => $user->id],
            [
                'subscription_plan_id' => $plan->id,
                'status' => 'active',
                'activated_at' => now(),
                'expires_at' => $this->calculateExpiryDate($user, $plan->duration_in_months),
            ]
        );

        // 4. Tandai token sudah digunakan
        $token->update([
            'is_used' => true,
            'used_by_user_id' => $user->id,
            'used_at' => now()
        ]);

        // 5. Buat log aktivitas
        $this->createLog(
            $subscription,
            'TOKEN_ACTIVATED',
            "Langganan diaktifkan dengan token '{$tokenString}' untuk paket '{$plan->name}'.",
            $user->id
        );

        return $subscription;
    }

    private function calculateExpiryDate(User $user, int $monthsToAdd): Carbon
    {
        $currentSubscription = $user->subscription;

        if ($currentSubscription && $currentSubscription->expires_at->isFuture()) {
            return $currentSubscription->expires_at->addMonths($monthsToAdd);
        }

        return Carbon::now()->addMonths($monthsToAdd);
    }

    private function createLog(Subscription $subscription, string $action, string $description, ?int $performerId = null)
    {
        SubscriptionLog::create([
            'subscription_id' => $subscription->id,
            'user_id' => $subscription->user_id,
            'action' => $action,
            'description' => $description,
            'performed_by_user_id' => $performerId,
        ]);
    }
}
