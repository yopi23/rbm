<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\WhatsappDevice;

class WhatsAppMessageController extends Controller
{
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.whatsapp.api_url', 'http://localhost:3000');
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'number'  => 'required|string',
            'message' => 'required|string',
        ]);

        // 🔹 1️⃣ Validasi Nomor WhatsApp
        $formattedNumber = $this->formatPhoneNumber($validated['number']);

        // 🔹 3️⃣ Ambil Perangkat yang Siap Digunakan
        $device = WhatsappDevice::where('status', 'READY')->first();

        if (!$device) {
            return response()->json([
                'status'  => false,
                'message' => 'No active WhatsApp device found',
            ], 401);
        }

        // 🔹 4️⃣ Kirim Pesan ke API WhatsApp Gateway
        $response = Http::post("{$this->apiBaseUrl}/message/send/{$device->session_id}", [
            'number'  => $formattedNumber,
            'message' => $validated['message'],
        ]);

        if ($response->successful()) {
            return response()->json([
                'status'  => true,
                'type'=>'chat',
                'message' => 'Message sent successfully',
                'data'    => $response->json(),
            ]);
        }

        return response()->json([
            'status'  => false,
            'message' => 'Failed to send message',
            'error'   => $response->json(),
        ], $response->status());
    }
    /**
     * 🔹 Format Nomor HP ke Standar Internasional (+62)
     */
    private function formatPhoneNumber($number)
    {
        // Hapus spasi, strip, dan karakter selain angka
        // Hapus semua karakter non-numerik
        $number = preg_replace('/[^0-9]/', '', $number);

        // Jika nomor kosong setelah pembersihan atau hanya berisi "0", return null
        if (empty($number) || $number === '0' || $number === '00') {
            return null;
        }

        // Jika nomor dimulai dengan 0, ganti dengan 62
        if (substr($number, 0, 1) === '0') {
            $number = '62' . substr($number, 1);
        }
        // Jika nomor belum dimulai dengan 62, tambahkan 62
        elseif (substr($number, 0, 2) !== '62') {
            $number = '62' . $number;
        }

        // Pastikan nomor memiliki panjang minimal 10 digit (termasuk 62)
        if (strlen($number) < 10) {
            return null;
        }

        return $number;
    }

    /**
     * 🔹 Cek Apakah Nomor Terdaftar di WhatsApp
     */
    private function isWhatsAppNumber($number)
    {
        $response = Http::get("{$this->apiBaseUrl}/check-number", [
            'number' => $number,
        ]);

        return $response->successful() && $response->json()['status'] === true;
    }

}
