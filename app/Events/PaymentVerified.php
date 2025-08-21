<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel; // Gunakan PrivateChannel agar aman
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentVerified implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $payment;

    public function __construct(Payment $payment)
    {
        $this->payment = $payment;
    }

    /**
     * Mendapatkan kanal broadcast.
     * Kita buat kanal privat untuk setiap user agar notifikasi tidak salah kirim.
     */
    public function broadcastOn()
    {
        return new PrivateChannel('user.' . $this->payment->user_id);
    }

    /**
     * Memberi nama event agar mudah dikenali di frontend.
     */
    public function broadcastAs()
    {
        return 'payment.verified';
    }
}
