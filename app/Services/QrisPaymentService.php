<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Str;

class QrisPaymentService
{
    /**
     * Membuat tagihan pembayaran yang pending dengan nominal unik.
     */
    public function generatePendingPayment(User $user, SubscriptionPlan $plan): Payment
    {
        // Hapus pembayaran pending sebelumnya dari user ini untuk menghindari duplikat
        Payment::where('user_id', $user->id)->where('status', 'pending')->delete();

        $uniqueAmount = $this->calculateUniqueAmount($plan->price, $user->id);

        return Payment::create([
            'user_id' => $user->id,
            'subscription_plan_id' => $plan->id,
            'unique_amount' => $uniqueAmount,
            'reference_code' => 'PAY-' . strtoupper(Str::random(10)),
            'status' => 'pending',
        ]);
    }

    /**
     * Mencari dan memproses pembayaran berdasarkan nominal unik.
     * Ini akan dipanggil oleh webhook.
     */
    public function findAndProcessPayment(int $amount): ?Payment
    {
        $payment = Payment::where('unique_amount', $amount)
            ->where('status', 'pending')
            ->first();

        if ($payment) {
            $payment->update(['status' => 'completed']);

            // Panggil service langganan untuk mengaktifkan
            (new SubscriptionService())->activateByPayment($payment);

            return $payment;
        }

        return null;
    }

    /**
     * Menghitung nominal unik untuk menghindari tabrakan.
     * Harga dasar + 3-4 digit acak.
     */
    private function calculateUniqueAmount(int $basePrice, int $userId): int
    {
        // Loop untuk memastikan nominal benar-benar unik
        do {
            // Kombinasi dari 3 digit akhir ID user dan 2 digit acak
            $uniqueCode = ($userId % 1000) + rand(10, 99);
            $amount = $basePrice + $uniqueCode;
        } while (Payment::where('unique_amount', $amount)->exists());

        return $amount;
    }
}
