<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use App\Services\SubscriptionService; // <-- Panggil Service
use App\Services\QrisGeneratorService;
use Illuminate\Support\Facades\Auth;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SubscriptionApiController extends Controller
{
    // Kita simpan instance service di sini agar bisa dipakai di semua method
    protected $subscriptionService;

    public function __construct(SubscriptionService $subscriptionService)
    {
        $this->subscriptionService = $subscriptionService;
    }

    public function getPendingPayments()
    {
        $pendingPayments = Payment::where('user_id', Auth::id())
            ->where('status', 'pending')
            ->with('subscriptionPlan')
            ->get();

        return response()->json($pendingPayments);
    }

    public function showPayment(SubscriptionPlan $plan, QrisGeneratorService $qrisService)
    {
        // Logika pembuatan pembayaran tetap di sini karena spesifik untuk request ini
        $payment = Payment::where('user_id', auth()->id())
            ->where('subscription_plan_id', $plan->id)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            $uniqueAmount = 0;
            do {
                $uniqueCode = rand(1, 999);
                $uniqueAmount = $plan->price + $uniqueCode;
            } while (Payment::where('unique_amount', $uniqueAmount)->exists());

            $payment = Payment::create([
                'user_id' => auth()->id(),
                'subscription_plan_id' => $plan->id,
                'unique_amount' => $uniqueAmount,
                'reference_code' => 'SUB-' . auth()->id() . '-' . time(),
                'status' => 'pending',
            ]);
        }

        $qrisString = $qrisService->generate($payment->unique_amount, $payment->reference_code);
        $options = new QROptions(['outputType' => QRCode::OUTPUT_MARKUP_SVG, 'eccLevel' => QRCode::ECC_L]);
        $qrCodeImage = (new QRCode($options))->render($qrisString);

        return response()->json(['payment' => $payment->load('subscriptionPlan'), 'qr_code_image' => $qrCodeImage]);
    }

    /**
     * Mengaktifkan langganan menggunakan token, sekarang memanggil Service.
     */
    public function activateWithToken(Request $request)
    {
        $request->validate(['token' => 'required|string']);

        try {
            // Panggil service untuk melakukan semua pekerjaan
            $this->subscriptionService->activateByToken(Auth::user(), $request->token);

            return response()->json(['message' => 'Langganan berhasil diaktifkan.']);

        } catch (\Exception $e) {
            // Tangkap error jika token tidak valid dari service
            return response()->json(['message' => $e->getMessage()], 404);
        }
    }

    public function cancelPayment(Payment $payment)
    {
        if ($payment->user_id !== Auth::id() || $payment->status !== 'pending') {
            return response()->json(['message' => 'Aksi tidak diizinkan.'], 403);
        }
        $payment->delete();
        return response()->json(['message' => 'Tagihan berhasil dibatalkan.']);
    }
}
