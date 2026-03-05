<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Client as GoogleClient;

class FCMService
{
    /**
     * Get OAuth2 Access Token from Service Account JSON
     */
    private static function getAccessToken()
    {
        try {
            $path = storage_path('app/firebase-auth.json');

            if (!file_exists($path)) {
                Log::error("FCM Error: Service account file not found at $path");
                return null;
            }

            $client = new GoogleClient();
            $client->setAuthConfig($path);
            $client->addScope('https://www.googleapis.com/auth/firebase.messaging');

            // --- PILIHAN SSL VERIFIKASI ---
            // 1. Untuk Lokal (Windows/XAMPP): Gunakan cacert.pem jika error SSL
            // $httpClient = new \GuzzleHttp\Client(['verify' => storage_path('app/cacert.pem')]);
            // $client->setHttpClient($httpClient);

            // 2. Untuk VPS/Production: Biarkan default (menggunakan sertifikat sistem)
            // Di VPS Linux biasanya tidak perlu setHttpClient manual untuk file .pem


            $token = $client->fetchAccessTokenWithAssertion();
            return $token['access_token'] ?? null;
        }
        catch (\Exception $e) {
            Log::error("FCM Auth Error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Mengirim push notification via FCM v1 API
     */
    public static function sendNotification($token, $title, $body, $data = [])
    {
        if (empty($token)) {
            Log::warning('FCM Debug: Token is empty, skipping.');
            return false;
        }

        $projectId = env('FCM_PROJECT_ID', 'yoyoycell-app');
        $accessToken = self::getAccessToken();

        if (!$accessToken) {
            Log::error('FCM Debug: Failed to get Access Token. Check firebase-auth.json');
            return false;
        }

        try {
            // Kita harus memastikan semua value di $data adalah STRING (Syarat FCM v1)
            $formattedData = [];
            foreach ($data as $key => $value) {
                $formattedData[(string)$key] = (string)$value;
            }

            $url = "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send";

            $payload = [
                'message' => [
                    'token' => $token,
                    'notification' => [
                        'title' => $title,
                        'body' => $body,
                    ],
                    'data' => $formattedData,
                    'android' => [
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                        ]
                    ]
                ]
            ];

            // 1. Versi Lokal (pakai cacert.pem)
            // $response = Http::withOptions(['verify' => storage_path('app/cacert.pem')])->withHeaders([

            // 2. Versi VPS (Biarkan sistem yang verifikasi)
            $response = Http::withHeaders([

                'Authorization' => 'Bearer ' . $accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, $payload);

            if ($response->successful()) {
                Log::info("FCM Debug: Success sending to $token");
                return true;
            }
            else {
                Log::error("FCM Debug: Failed. Response: " . $response->body());
                return false;
            }
        }
        catch (\Exception $e) {
            Log::error('FCM Debug: Exception: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kirim ke banyak token
     */
    public static function sendToMultiple($tokens, $title, $body, $data = [])
    {
        foreach ($tokens as $token) {
            self::sendNotification($token, $title, $body, $data);
        }
    }
}
