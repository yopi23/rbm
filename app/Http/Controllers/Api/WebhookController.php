<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Events\PaymentVerified;
use App\Models\Payment;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Events\QrisMutationReceived; // Pastikan ini diimpor
use Illuminate\Support\Facades\Cache; // Pastikan ini diimpor

class WebhookController extends Controller
{
    /**
     * WEBHOOK UTAMA - Handle semua notifikasi dari Macrodroid
     * Endpoint: POST /api/webhooks/macrodroid
     */
    public function handleMacrodroid(Request $request, SubscriptionService $subscriptionService)
    {
        // ====================================================================
        // STEP 1: VERIFIKASI KUNCI OWNER & AMBIL TOKEN TAMBAHAN
        // ====================================================================
        $secretKey = $request->header('X-Secret-Key'); // Kunci Owner (Wajib)
        // Kunci tambahan dari Macrodroid untuk Verifikasi Langganan (Opsional)
        $receivedVerificationToken = $request->header('X-Payment-Token');
        // Kunci Master dari .env/config
        $masterVerificationToken = config('app.macrodroid_secret_key');

        if (empty($secretKey)) {
            Log::warning('Webhook: Secret key tidak ditemukan');
            return response()->json(['message' => 'Secret key required'], 401);
        }

        // Cari owner/admin berdasarkan secret key
        $owner = DB::table('user_details')
            ->where('macrodroid_secret', $secretKey)
            ->first();

        if (!$owner) {
            Log::warning('Webhook: Secret key tidak valid', ['secret' => $secretKey]);
            return response()->json(['message' => 'Invalid secret key'], 403);
        }

        Log::info('Webhook Payload Diterima:', [
            'owner_id' => $owner->id,
            'owner_name' => $owner->fullname,
            'token_langganan_status' => !empty($receivedVerificationToken) ? 'Tersedia' : 'Kosong',
            'payload' => $request->all()
        ]);

        // ====================================================================
        // STEP 2: EKSTRAK NOMINAL DARI NOTIFIKASI BANK
        // ====================================================================
        $validated = $request->validate([
            'notification_text' => 'required|string',
        ]);

        // Gunakan regex untuk mencari nominal
        preg_match('/Rp\.?\s*([\d\.,]+)/', $validated['notification_text'], $matches);

        if (!isset($matches[1])) {
            Log::warning('Webhook: Nominal tidak ditemukan', [
                'text' => $validated['notification_text']
            ]);
            return response()->json(['message' => 'Amount not found in notification'], 400);
        }

        // Hapus titik/koma dan konversi ke integer
        $amount = (int) str_replace(['.', ','], '', $matches[1]);

        if ($amount <= 0) {
            Log::warning('Webhook: Nominal tidak valid', ['amount' => $amount]);
            return response()->json(['message' => 'Invalid amount'], 400);
        }

        Log::info('Webhook: Nominal berhasil diekstrak', ['amount' => $amount]);

        // ====================================================================
        // STEP 3: CEK APAKAH INI PEMBAYARAN SUBSCRIPTION (HARUS DENGAN TOKEN TAMBAHAN)
        // ====================================================================

        // Cek apakah notifikasi ini datang dari Macrodroid yang DITUGASKAN sebagai QRIS Administrator/Verifikator.
        if (!empty($receivedVerificationToken) && $receivedVerificationToken === $masterVerificationToken) {

            // TOKEN LANGGANAN COCOK! Cari Payment berdasarkan nominal unik.
            $payment = Payment::where('unique_amount', $amount)
                ->where('status', 'pending')
                ->first();

            if ($payment) {
                // INI ADALAH PEMBAYARAN SUBSCRIPTION YANG TERVERIFIKASI!
                Log::info('Webhook: Terdeteksi sebagai SUBSCRIPTION PAYMENT (VERIFIED by X-Payment-Token)', [
                    'payment_id' => $payment->id,
                    'reference_code' => $payment->reference_code
                ]);

                return $this->handleSubscriptionPayment($payment, $subscriptionService);
            }

            // Jika token valid, tapi payment tidak ditemukan, anggap ini notifikasi bank biasa
            // dan lanjutkan ke Daily Transaction.
            Log::info('Webhook: Token Langganan Valid, tapi Payment Pending tidak ditemukan. Lanjut ke Daily Transaction.', ['amount' => $amount]);

        } else if (!empty($receivedVerificationToken)) {
            // Token dikirim tapi salah. Log untuk debugging/keamanan.
            Log::warning('Webhook: Token Langganan TIDAK VALID. Lanjut ke Daily Transaction.', [
                'received_token' => $receivedVerificationToken
            ]);
        }

        // ====================================================================
        // STEP 4: BUKAN SUBSCRIPTION (ATAU VERIFIKASI SUBSCRIPTION GAGAL) = TRANSAKSI KASIR HARIAN
        // ====================================================================
        Log::info('Webhook: Terdeteksi sebagai DAILY TRANSACTION (Kasir)');

        return $this->handleDailyTransaction($owner, $amount, $validated['notification_text']);
    }

    /**
     * Handler untuk SUBSCRIPTION PAYMENT
     */
    private function handleSubscriptionPayment(Payment $payment, SubscriptionService $subscriptionService)
    {
        try {
            $payment->update(['status' => 'completed']);
            $subscriptionService->activateByPayment($payment);

            // Gunakan event PaymentVerified yang sudah ada
            // Asumsi event ini memiliki logika pengiriman notif ke user/owner.
            event(new PaymentVerified($payment));

            Log::info('Webhook: Subscription berhasil diaktifkan', [
                'payment_id' => $payment->id,
                'reference_code' => $payment->reference_code,
                'user_id' => $payment->user_id,
            ]);

            return response()->json([
                'status' => true,
                'type' => 'subscription',
                'message' => 'Subscription activated successfully',
                'data' => [
                    'payment_id' => $payment->id,
                    'reference_code' => $payment->reference_code,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook: Gagal mengaktifkan subscription', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to activate subscription'], 500);
        }
    }

    /**
     * Handler untuk DAILY TRANSACTION (Transaksi Kasir)
     */
    private function handleDailyTransaction($owner, int $amount, string $notificationText)
    {
        try {
            // ================================================================
            // STEP A: TENTUKAN KASIR YANG SEDANG AKTIF (Hanya dari cache)
            // ================================================================
            $kasirDetailId = Cache::get("active_kasir_{$owner->id}");

            // Default ke owner sendiri jika cache kosong
            if (!$kasirDetailId) {
                $kasirDetailId = $owner->id;
                Log::info('Webhook: Cache kasir aktif kosong, gunakan owner sebagai kasir', [
                    'owner_id' => $owner->id
                ]);
            }

            // Validasi dan Keamanan Kasir
            $kasir = DB::table('user_details')->where('id', $kasirDetailId)->first();

            if (!$kasir) {
                Log::warning('Webhook: Kasir tidak ditemukan. Fallback ke owner.', ['kasir_id' => $kasirDetailId]);
                $kasirDetailId = $owner->id;
                $kasir = $owner; // Pastikan $kasir di-set ulang ke $owner untuk nama kasir di response
            }

            // KEAMANAN: Pastikan kasir adalah owner atau karyawan owner
            $isAuthorized = (
                $kasir->id == $owner->id ||
                $kasir->id_upline == $owner->id
            );

            if (!$isAuthorized) {
                Log::warning('Webhook: Kasir tidak authorized untuk owner ini. Fallback ke owner.', [
                    'owner_id' => $owner->id,
                    'kasir_id' => $kasirDetailId,
                ]);
                $kasirDetailId = $owner->id;
                $kasir = $owner; // Set $kasir kembali ke $owner
            }

            // ================================================================
            // STEP B: SIMPAN KE DATABASE MUTASI_QRIS
            // ================================================================
            $mutasiId = DB::table('mutasi_qris')->insertGetId([
                'owner_detail_id' => $owner->id,
                'kasir_detail_id' => $kasirDetailId,
                'nominal' => $amount,
                'keterangan' => $this->extractKeterangan($notificationText),
                'status' => 'new',
                'reported_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $mutasi = DB::table('mutasi_qris')->where('id', $mutasiId)->first();

            // ================================================================
            // STEP C: KIRIM NOTIFIKASI REAL-TIME KE KASIR VIA PUSHER
            // ================================================================
            // Gunakan event QrisMutationReceived
            event(new QrisMutationReceived(
                $owner->id,
                $kasirDetailId,
                (array) $mutasi
            ));

            Log::info('Webhook: Daily transaction berhasil dicatat', [
                'mutasi_id' => $mutasiId,
                'owner_id' => $owner->id,
                'kasir_id' => $kasirDetailId,
                'nominal' => $amount,
            ]);

            return response()->json([
                'status' => true,
                'type' => 'daily_transaction',
                'message' => 'Daily transaction recorded successfully',
                'data' => [
                    'mutasi_id' => $mutasiId,
                    'nominal' => $amount,
                    'owner_name' => $owner->fullname,
                    'kasir_name' => $kasir->fullname ?? 'Unknown Kasir',
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('Webhook: Gagal mencatat daily transaction', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['message' => 'Failed to record transaction'], 500);
        }
    }

    /**
     * Ekstrak keterangan dari teks notifikasi
     */
    private function extractKeterangan(string $text): string
    {
        // Hapus nominal dari teks
        $cleaned = preg_replace('/Rp\.?\s*[\d\.,]+/', '', $text);

        // Ambil maksimal 190 karakter
        return trim(substr($cleaned, 0, 190)) ?: 'Transfer masuk';
    }

    /**
     * Set kasir yang sedang aktif (dipanggil dari Flutter)
     * Endpoint: POST /api/webhook/set-active-kasir
     */
    public function setActiveKasir(Request $request)
    {
        $validated = $request->validate([
            'owner_detail_id' => 'required|integer',
            'kasir_detail_id' => 'required|integer',
        ]);

        // Simpan ke cache untuk 8 jam
        Cache::put(
            "active_kasir_{$validated['owner_detail_id']}",
            $validated['kasir_detail_id'],
            now()->addHours(8)
        );

        Log::info('Active kasir updated', [
            'owner_id' => $validated['owner_detail_id'],
            'kasir_id' => $validated['kasir_detail_id'],
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Active kasir set successfully'
        ]);
    }
}
