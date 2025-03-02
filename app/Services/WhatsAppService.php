<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\WhatsappDevice;

class WhatsAppService
{
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.whatsapp.api_url', 'http://localhost:3000');
    }

    /**
     * Mengirim pesan WhatsApp ke nomor yang ditentukan
     *
     * @param string $phoneNumber Nomor telepon penerima
     * @param string $message Pesan yang akan dikirim
     * @param string|null $deviceId ID sesi khusus (opsional)
     * @return array Response dari API
     */
    public function sendMessage($phoneNumber, $message, $deviceId = null)
    {

        try {
            // Validasi nomor telepon
            if (empty($phoneNumber) || $phoneNumber === '0' || $phoneNumber === '00') {
                Log::warning("WhatsApp message not sent: Empty or invalid phone number", [
                    'phone' => $phoneNumber
                ]);

                return [
                    'status' => false,
                    'message' => 'Invalid phone number: Number is empty or just zeros',
                    'whatsapp_sent' => false
                ];
            }

            // Format nomor telepon
            $formattedNumber = $this->formatPhoneNumber($phoneNumber);

            if (!$formattedNumber) {
                Log::warning("WhatsApp message not sent: Invalid phone number format", [
                    'phone' => $phoneNumber
                ]);

                return [
                    'status' => false,
                    'message' => 'Invalid phone number format',
                    'whatsapp_sent' => false
                ];
            }

            // Jika device ID tidak diberikan, gunakan device aktif pertama
            if (!$deviceId) {
                $device = WhatsappDevice::where('status', 'READY')->first();

                if (!$device) {
                    Log::warning("WhatsApp message not sent: No active WhatsApp device found");

                    return [
                        'status' => false,
                        'message' => 'No active WhatsApp device found',
                        'whatsapp_sent' => false
                    ];
                }

                $deviceId = $device->session_id;
            }

            // Kirim request ke WhatsApp API
            $response = Http::post("{$this->apiBaseUrl}/send/{$deviceId}", [
                'number' => $formattedNumber,
                'message' => $message
            ]);

            if (!$response->successful()) {
                Log::error("Failed to send WhatsApp message", [
                    'phone' => $formattedNumber,
                    'response' => $response->json()
                ]);

                return [
                    'status' => false,
                    'message' => 'Failed to send message',
                    'data' => $response->json(),
                    'whatsapp_sent' => false
                ];
            }

            Log::info("WhatsApp message sent successfully", [
                'phone' => $formattedNumber
            ]);

            return [
                'status' => true,
                'message' => 'Message sent successfully',
                'data' => $response->json(),
                'whatsapp_sent' => true
            ];
        } catch (\Exception $e) {
            Log::error("Error sending WhatsApp message: " . $e->getMessage(), [
                'exception' => $e
            ]);

            return [
                'status' => false,
                'message' => 'Error sending message: ' . $e->getMessage(),
                'whatsapp_sent' => false
            ];
        }
    }

    /**
     * Kirim template notifikasi service selesai
     *
     * @param array $data Data service (nomor_services, nama_barang, no_hp)
     * @return array Response dari API
     */
    public function sendServiceCompletionNotification($data)
    {
        // Validasi nomor telepon terlebih dahulu
        if (empty($data['no_hp']) || $data['no_hp'] === '0' || $data['no_hp'] === '00') {
            return [
                'status' => false,
                'message' => 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak valid',
                'whatsapp_sent' => false
            ];
        }

        $message = "Halo Kak, terimakasih telah mempercayakan perbaikan perangkat Anda kepada kami. " .
                   "Kami ingin memberitahu bahwa perbaikan untuk barang *{$data['nama_barang']}* dengan nomor service *{$data['nomor_services']}* telah *SELESAI*. " .
                   "Silakan datang ke toko kami untuk mengambil barang Anda. " ."\n\n".
                   "⚠️PERHATIAN⚠️"."\n".
                   "*Unit yang tidak diambil dalam jangka waktu 3 bulan, baik yang telah selesai diperbaiki maupun yang belum, akan dianggap sebagai unit yang telah diserahkan kepada kami.*"."\n\n".
                   "Jika ada pertanyaan, jangan ragu untuk menghubungi kami. Terima kasih!";

        return $this->sendMessage($data['no_hp'], $message);
    }
    public function penarikanNotification($data)
    {
        // Cek apakah no_hp adalah array atau string
        $phoneNumbers = is_array($data['no_hp']) ? $data['no_hp'] : [$data['no_hp']];
        $results = [];

        foreach ($phoneNumbers as $phoneNumber) {
            // Validasi nomor telepon
            if (empty($phoneNumber) || $phoneNumber === '0' || $phoneNumber === '00') {
                $results[$phoneNumber] = [
                    'status' => false,
                    'message' => 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak valid',
                    'whatsapp_sent' => false
                ];
                continue;
            }

            try {
                $message = "⚠️PENARIKAN⚠️"."\n".
                        "Penarikan dana *{$data['jumlah']}*" ."\n".
                        "Oleh *{$data['teknisi']}*"."\n"."Catatan : {$data['catatan']}";

                // Kirim pesan menggunakan method sendMessage yang sudah ada
                $sendResult = $this->sendMessage($phoneNumber, $message);

                $results[$phoneNumber] = $sendResult;
            } catch (\Exception $e) {
                $results[$phoneNumber] = [
                    'status' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                    'whatsapp_sent' => false
                ];
            }
        }

        // Return overall status
        $allSuccess = !in_array(false, array_column($results, 'status'));
        return [
            'status' => $allSuccess,
            'message' => $allSuccess ? 'Semua pesan berhasil dikirim' : 'Beberapa pesan gagal dikirim',
            'details' => $results,
            'whatsapp_sent' => $allSuccess
        ];
    }

    /**
     * Format nomor telepon untuk WhatsApp (08xx -> 628xx)
     *
     * @param string $phoneNumber
     * @return string|null
     */
    public function formatPhoneNumber($phoneNumber)
    {
        // Hapus semua karakter non-numerik
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Jika nomor kosong setelah pembersihan atau hanya berisi "0", return null
        if (empty($phoneNumber) || $phoneNumber === '0' || $phoneNumber === '00') {
            return null;
        }

        // Jika nomor dimulai dengan 0, ganti dengan 62
        if (substr($phoneNumber, 0, 1) === '0') {
            $phoneNumber = '62' . substr($phoneNumber, 1);
        }
        // Jika nomor belum dimulai dengan 62, tambahkan 62
        elseif (substr($phoneNumber, 0, 2) !== '62') {
            $phoneNumber = '62' . $phoneNumber;
        }

        // Pastikan nomor memiliki panjang minimal 10 digit (termasuk 62)
        if (strlen($phoneNumber) < 10) {
            return null;
        }

        return $phoneNumber;
    }

    /**
     * Verifikasi apakah nomor telepon valid untuk WhatsApp
     *
     * @param string $phoneNumber
     * @return bool
     */
    public function isValidPhoneNumber($phoneNumber)
    {
        // Cek apakah nomor kosong
        if (empty($phoneNumber)) {
            return false;
        }

        // Bersihkan nomor
        $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

        // Jika hanya berisi 0
        if ($phoneNumber === '0' || $phoneNumber === '00') {
            return false;
        }

        // Pastikan nomor memiliki minimal 9 digit (tidak termasuk kode negara)
        if (strlen($phoneNumber) < 9) {
            return false;
        }

        return true;
    }
}
