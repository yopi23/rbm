<?php

namespace App\Http\Controllers\Admin; // Lokasi disesuaikan

use App\Http\Controllers\Controller;
use App\Services\QrisGeneratorService;
use Illuminate\Http\Request;
use App\Models\SubscriptionPlan;
use App\Models\Payment;
use App\Services\QrisPaymentService;
use App\Services\SubscriptionService; // Gunakan service untuk logika
use Illuminate\Support\Facades\Auth;

// Library untuk generate QR Code
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

class SubscriptionController extends Controller
{
    /**
     * Menampilkan halaman utama status langganan & pilihan paket.
     */
    public function index()
{
    $page = "Langganan Saya";
    $user = Auth::user();

    // Ambil data langganan aktif
    $subscription = $user->subscription;

    // Ambil daftar paket
    $plans = SubscriptionPlan::orderBy('price')->get();

    // === TAMBAHKAN INI ===
    // Ambil semua pembayaran yang masih pending milik user ini
    $pendingPayments = Payment::where('user_id', $user->id)
        ->where('status', 'pending')
        ->with('subscriptionPlan') // Ambil juga relasi ke nama paketnya
        ->get();
    // ======================

    // Render view konten ke dalam variabel $content, tambahkan variabel baru
    $content = view('admin.page.subscriptions.content', compact(
        'subscription',
        'plans',
        'pendingPayments' // <<-- Kirim data tagihan ke view
    ))->render();

    // Tampilkan menggunakan layout blank_page
    return view('admin.layout.blank_page', compact('page', 'content'));
}

    /**
     * Menampilkan halaman instruksi pembayaran QRIS.
     */
    // public function showPayment(SubscriptionPlan $plan, QrisPaymentService $paymentService)
    // {
    //     $page = "Instruksi Pembayaran";

    //     $user = Auth::user();
    //     $payment = $paymentService->generatePendingPayment($user, $plan);

    //     // Render view konten pembayaran ke dalam variabel $content
    //     $content = view('admin.page.subscriptions.payment_content', compact('plan', 'payment'))->render();

    //     // Tampilkan menggunakan layout blank_page
    //     return view('admin.layout.blank_page', compact('page', 'content'));
    // }
    public function showPayment(SubscriptionPlan $plan, QrisGeneratorService $qrisService)
    {
        $page = "Pembayaran Langganan";

        $payment = Payment::where('user_id', auth()->id())
        ->where('subscription_plan_id', $plan->id)
        ->where('status', 'pending')
        ->first();

        if(!$payment){
            // =================================================================
            // LOGIKA BARU: Menghitung Nominal Unik
            // =================================================================
            $uniqueAmount = 0;
            do {
                // 1. Buat kode unik antara 1 - 999
                $uniqueCode = rand(1, 999);
                // 2. Tambahkan ke harga asli paket
                $uniqueAmount = $plan->price + $uniqueCode;
                // 3. Cek ke database apakah nominal ini sudah ada.
                //    Jika sudah, ulangi lagi untuk mendapatkan angka baru.
            } while (Payment::where('unique_amount', $uniqueAmount)->exists());
            // =================================================================

            // Buat record pembayaran menggunakan nominal yang sudah dijamin unik
            $payment = Payment::create([
                'user_id' => auth()->id(),
                'subscription_plan_id' => $plan->id,
                'unique_amount' => $uniqueAmount, // <<---- Gunakan nominal unik
                'reference_code' => 'SUB-' . auth()->id() . '-' . time(),
                'status' => 'pending',
            ]);
        };
        // Generate string QRIS menggunakan nominal unik
        $qrisString = $qrisService->generate($payment->unique_amount, $payment->reference_code);

        // Konfigurasi output gambar QR Code
        $options = new QROptions([
            'outputType' => QRCode::OUTPUT_MARKUP_SVG,
            'eccLevel' => QRCode::ECC_L,
            'version' => QRCode::VERSION_AUTO,
        ]);

        // Generate gambar QR Code dari string
        $qrCodeImage = (new QRCode($options))->render($qrisString);

        // Render view dengan data pembayaran (termasuk nominal unik)
        $content = view('admin.page.subscriptions.dynamic_payment', compact('plan', 'payment', 'qrCodeImage'))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function cancelPayment(Payment $payment)
    {
        // Keamanan: Pastikan hanya pemilik yang bisa membatalkan
        if ($payment->user_id !== Auth::id() || $payment->status !== 'pending') {
            abort(403, 'Aksi tidak diizinkan.');
        }

        $payment->delete();

        return redirect()->route('subscriptions.index')->with('success', 'Pembayaran telah dibatalkan. Silakan pilih paket lain.');
    }

    /**
     * Memproses aktivasi langganan menggunakan token.
     * Method ini tidak menampilkan view, hanya redirect, jadi tidak perlu diubah.
     */
    public function activateWithToken(Request $request, SubscriptionService $subscriptionService)
    {
        $request->validate(['token' => 'required|string']);

        try {
            // Panggil service untuk mengaktifkan token
            $subscriptionService->activateByToken(Auth::user(), $request->token);
            return redirect()->route('subscriptions.index')->with('success', 'Selamat! Langganan Anda telah diaktifkan.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
