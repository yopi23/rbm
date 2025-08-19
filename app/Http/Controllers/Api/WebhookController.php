<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\QrisPaymentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function handleQris(Request $request)
    {
        // 1. KEAMANAN: Verifikasi Signature/Token dari Payment Gateway
        // Setiap payment gateway punya cara sendiri. Ini CONTOH.
        $signature = $request->header('X-Signature');
        $secretKey = config('services.payment_gateway.secret');

        if (!$this->isValidSignature($request->getContent(), $signature, $secretKey)) {
            Log::warning('Webhook QRIS: Signature tidak valid.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'Invalid signature.'], 403);
        }

        // 2. KEAMANAN: IP Whitelisting (opsional tapi sangat disarankan)
        $allowedIps = config('services.payment_gateway.allowed_ips');
        if (!in_array($request->ip(), $allowedIps)) {
             Log::warning('Webhook QRIS: Akses dari IP tidak diizinkan.', ['ip' => $request->ip()]);
            return response()->json(['message' => 'IP not allowed.'], 403);
        }

        // Ambil data nominal dari body request (sesuaikan dengan format gateway Anda)
        $amount = $request->input('data.amount');

        if (!$amount) {
            return response()->json(['message' => 'Amount not found.'], 400);
        }

        // Proses pembayaran menggunakan service
        $paymentService = new QrisPaymentService();
        $payment = $paymentService->findAndProcessPayment((int)$amount);

        if ($payment) {
            Log::info("Webhook QRIS: Pembayaran berhasil diproses untuk Ref: {$payment->reference_code}");
            return response()->json(['message' => 'Webhook processed successfully.']);
        }

        Log::warning('Webhook QRIS: Pembayaran tidak ditemukan atau sudah diproses.', ['amount' => $amount]);
        return response()->json(['message' => 'Payment not found or already processed.'], 404);
    }

    private function isValidSignature(string $payload, string $signature, string $secret): bool
    {
        // Implementasi hash_hmac sesuai dokumentasi payment gateway
        // Contoh: return hash_equals(hash_hmac('sha256', $payload, $secret), $signature);
        return true; // Ganti dengan implementasi nyata!
    }
}
