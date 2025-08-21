<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PaymentVerified;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log; // Penting untuk debugging

class WebhookController extends Controller
{
    /**
     * Menerima notifikasi pembayaran yang diteruskan oleh Macrodroid.
     */
    public function handleMacrodroid(Request $request, SubscriptionService $subscriptionService)
    {

        // Langkah A: Verifikasi Keamanan dengan Kunci Rahasia
        $secretKey = $request->header('X-Secret-Key');
        if ($secretKey !== config('app.macrodroid_secret_key')) {
            Log::warning('Webhook Macrodroid: Kunci rahasia tidak valid.');
            return response()->json(['message' => 'Invalid secret key.'], 403);
        }
         Log::info('Webhook Payload Diterima:', $request->all());

        // Langkah B: Validasi & Ekstrak Nominal dari Teks Notifikasi
        $validated = $request->validate([
            'notification_text' => 'required|string', // Kita sekarang menerima teks notifikasi
        ]);

        // Gunakan regular expression untuk mencari angka setelah "Rp"
        preg_match('/Rp\.?\s*([\d\.]+)/', $validated['notification_text'], $matches);

        if (!isset($matches[1])) {
            Log::info('Webhook Macrodroid: Nominal tidak ditemukan di teks notifikasi.', [
                'text' => $validated['notification_text']
            ]);
            return response()->json(['message' => 'Amount not found in notification text.'], 400);
        }

        // Hapus titik ribuan dan konversi ke integer
        $amount = (int) str_replace('.', '', $matches[1]);

        // Langkah C: Cari tagihan di database (logika Anda selanjutnya sudah benar)
        $payment = Payment::where('unique_amount', $amount)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            Log::info('Webhook Macrodroid: Pembayaran tidak ditemukan atau sudah diproses.', [
                'amount' => $amount
            ]);
            return response()->json(['message' => 'Payment not found or already processed.'], 404);
        }

        // Langkah D: Proses pembayaran dan aktifkan langganan
        try {
            // Ubah status pembayaran menjadi selesai
            $payment->update(['status' => 'completed']);

            // Panggil service untuk mengaktifkan langganan dan membuat log
            $subscriptionService->activateByPayment($payment);

            // Kirim notifikasi ke user melalui Pusher
            event(new PaymentVerified($payment));
            // ---------------------------------------------


            Log::info('Webhook Macrodroid: Langganan berhasil diaktifkan.', [
                'reference_code' => $payment->reference_code,
                'user_id' => $payment->user_id,
            ]);

            return response()->json(['message' => 'Subscription activated successfully.']);

        } catch (\Exception $e) {
            Log::error('Webhook Macrodroid: Gagal mengaktifkan langganan.', [
                'error' => $e->getMessage()
            ]);
            return response()->json(['message' => 'Failed to activate subscription.'], 500);
        }
    }
}
