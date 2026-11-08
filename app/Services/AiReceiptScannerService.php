<?php

namespace App\Services;

use App\Models\TokoSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiReceiptScannerService
{
    /**
     * Google Gemini Generative Language API endpoint.
     */
    protected const GEMINI_API_URL = 'https://generativelanguage.googleapis.com/v1beta/models';
    protected const DEFAULT_MODEL = 'gemini-2.5-flash';
    protected const FALLBACK_MODEL = 'gemini-1.5-flash';

    /**
     * Normalisasi nama model Gemini.
     */
    public static function normalizeModelName(?string $modelName): string
    {
        if (empty($modelName)) {
            return self::DEFAULT_MODEL;
        }

        $name = trim($modelName, " \t\n\r\0\x0B\"'");
        $lower = strtolower($name);

        if (in_array($lower, ['2.5', '2.5 flash', '2.5-flash', 'gemini 2.5', 'gemini-2.5'], true)) {
            return 'gemini-2.5-flash';
        }

        if (in_array($lower, ['1.5', '1.5 flash', '1.5-flash', 'gemini 1.5', 'gemini-1.5'], true)) {
            return 'gemini-1.5-flash';
        }

        if (in_array($lower, ['3.6', '3.6 flash', '3.6-flash', 'gemini 3.6', 'gemini-3.6'], true)) {
            return 'gemini-3.6-flash';
        }

        $clean = str_replace('models/', '', $name);
        if (!str_starts_with(strtolower($clean), 'gemini-')) {
            $clean = 'gemini-' . $clean;
        }

        return $clean;
    }

    /**
     * Ambil model default dari config/env atau fallback.
     */
    public static function getDefaultModel(): string
    {
        $configured = config('services.gemini.model') ?: env('GEMINI_MODEL');
        if (!empty($configured)) {
            return self::normalizeModelName($configured);
        }
        return self::DEFAULT_MODEL;
    }

    /**
     * Ambil model fallback dari config/env.
     */
    public static function getFallbackModel(): string
    {
        $configured = config('services.gemini.fallback_model') ?: env('GEMINI_FALLBACK_MODEL');
        if (!empty($configured)) {
            return self::normalizeModelName($configured);
        }
        return self::FALLBACK_MODEL;
    }

    /**
     * Ambil konfigurasi AI milik owner tertentu.
     */
    public static function getConfig(int $ownerId): array
    {
        $isConfigured = false;
        $maskedKey = null;
        $isEnabled = true;
        $model = self::getDefaultModel();

        try {
            $setting = TokoSetting::where('id_owner', $ownerId)->first();
            if ($setting) {
                if (isset($setting->ai_enabled)) {
                    $isEnabled = (bool) $setting->ai_enabled;
                }
                if (!empty($setting->ai_model)) {
                    $model = self::normalizeModelName($setting->ai_model);
                }

                if (!empty($setting->ai_gemini_api_key)) {
                    try {
                        $rawKey = Crypt::decryptString($setting->ai_gemini_api_key);
                    } catch (\Throwable $e) {
                        $rawKey = $setting->ai_gemini_api_key;
                    }

                    if (!empty($rawKey)) {
                        $isConfigured = true;
                        $len = strlen($rawKey);
                        $maskedKey = $len > 10
                            ? substr($rawKey, 0, 6) . '...' . substr($rawKey, -4)
                            : '******';
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal membaca ai config dari toko_settings: " . $e->getMessage());
        }

        // Fallback ke server global platform key jika owner belum set
        if (!$isConfigured) {
            $platformKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
            if (!empty($platformKey)) {
                $isConfigured = true;
                $len = strlen($platformKey);
                $maskedKey = 'Platform Server (' . ($len > 8 ? substr($platformKey, 0, 4) . '...' . substr($platformKey, -3) : 'Aktif') . ')';
            }
        }

        return [
            'is_configured' => $isConfigured,
            'masked_key' => $maskedKey,
            'is_enabled' => $isEnabled,
            'model' => $model,
        ];
    }

    /**
     * Ambil API Key terdekripsi (internal).
     */
    protected static function getDecryptedApiKey(int $ownerId): ?string
    {
        try {
            $setting = TokoSetting::where('id_owner', $ownerId)->first();
            if ($setting && !empty($setting->ai_gemini_api_key)) {
                try {
                    $decrypted = Crypt::decryptString($setting->ai_gemini_api_key);
                    if (!empty($decrypted)) {
                        return trim($decrypted);
                    }
                } catch (\Throwable $e) {
                    return trim($setting->ai_gemini_api_key);
                }
            }
        } catch (\Throwable $e) {
            Log::warning("Gagal ambil ai_gemini_api_key dari DB: " . $e->getMessage());
        }

        // Fallback ke platform .env key
        $platformKey = config('services.gemini.api_key') ?: env('GEMINI_API_KEY');
        if (!empty($platformKey)) {
            return trim($platformKey);
        }

        return null;
    }

    /**
     * Simpan konfigurasi AI milik owner.
     */
    public static function saveConfig(int $ownerId, array $data): array
    {
        $payload = [];

        if (isset($data['api_key'])) {
            $rawKey = trim((string) $data['api_key'], " \t\n\r\0\x0B\"'");
            if (!empty($rawKey)) {
                $payload['ai_gemini_api_key'] = Crypt::encryptString($rawKey);
            }
        }

        if (isset($data['is_enabled'])) {
            $payload['ai_enabled'] = (bool) $data['is_enabled'];
        }

        if (isset($data['model'])) {
            $payload['ai_model'] = self::normalizeModelName((string) $data['model']);
        }

        if (!empty($payload)) {
            TokoSetting::updateOrCreate(
                ['id_owner' => $ownerId],
                $payload
            );
        }

        return self::getConfig($ownerId);
    }

    /**
     * Uji koneksi API Key ke Google Gemini.
     */
    public static function testConnection(int $ownerId, ?string $providedApiKey = null): array
    {
        $cleanProvidedKey = null;
        if (!empty($providedApiKey)) {
            $cleanProvidedKey = trim($providedApiKey, " \t\n\r\0\x0B\"'");
        }

        $apiKey = !empty($cleanProvidedKey) ? $cleanProvidedKey : self::getDecryptedApiKey($ownerId);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'message' => 'API Key belum disetel atau kosong. Silakan tempel API Key Google Gemini terlebih dahulu.',
            ];
        }

        $candidateModels = [
            self::getDefaultModel(),
            self::getFallbackModel(),
            'gemini-2.5-flash',
            'gemini-1.5-flash',
        ];
        $candidateModels = array_values(array_unique($candidateModels));

        $lastError = 'Koneksi gagal.';

        foreach ($candidateModels as $candidateModel) {
            $url = self::GEMINI_API_URL . '/' . $candidateModel . ':generateContent?key=' . urlencode($apiKey);

            try {
                $response = Http::timeout(10)->post($url, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => 'Ping']
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.0,
                        'maxOutputTokens' => 10,
                    ],
                ]);

                if ($response->successful()) {
                    return [
                        'success' => true,
                        'active_model' => $candidateModel,
                        'message' => "Koneksi ke Google Gemini AI ({$candidateModel}) berhasil terhubung!",
                    ];
                }

                $errorData = $response->json();
                $lastError = $errorData['error']['message'] ?? 'HTTP ' . $response->status();

                // Jika API key tidak valid, langsung hentikan
                if ($response->status() === 400 && (
                    str_contains(strtolower($lastError), 'api key not valid') ||
                    str_contains(strtolower($lastError), 'api_key_invalid')
                )) {
                    break;
                }
            } catch (\Throwable $e) {
                $lastError = 'Koneksi ke Google AI terputus/timeout: ' . $e->getMessage();
            }
        }

        return [
            'success' => false,
            'message' => 'Gagal verifikasi API Key: ' . $lastError,
        ];
    }

    /**
     * Analisa gambar nota / faktur / surat jalan menggunakan Gemini Vision API.
     */
    public static function scanReceipt(int $ownerId, UploadedFile $file, string $mode = 'purchase'): array
    {
        $apiKey = self::getDecryptedApiKey($ownerId);

        if (empty($apiKey)) {
            return [
                'success' => false,
                'error_code' => 'AI_KEY_NOT_CONFIGURED',
                'message' => 'API Key Google Gemini toko Anda belum dikonfigurasi. Silakan masukkan API Key di menu Pengaturan AI Toko.',
            ];
        }

        $config = self::getConfig($ownerId);
        if (!$config['is_enabled']) {
            return [
                'success' => false,
                'error_code' => 'AI_DISABLED',
                'message' => 'Fitur Asisten AI sedang dinonaktifkan di pengaturan toko Anda.',
            ];
        }

        $model = self::normalizeModelName($config['model'] ?? null);

        // Streaming in-memory file gambar
        $mimeType = $file->getMimeType() ?: 'image/jpeg';
        $base64Image = base64_encode(file_get_contents($file->getRealPath()));

        // Bangun system prompt cerdas
        $prompt = self::buildSystemPrompt($mode);

        $url = self::GEMINI_API_URL . '/' . $model . ':generateContent?key=' . urlencode($apiKey);

        try {
            $response = Http::timeout(40)->post($url, [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt],
                            [
                                'inline_data' => [
                                    'mime_type' => $mimeType,
                                    'data' => $base64Image,
                                ]
                            ]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.1,
                    'responseMimeType' => 'application/json',
                ],
            ]);

            // Jika gagal, coba fallback model
            $fallbackModel = self::getFallbackModel();
            if (!$response->successful() && $model !== $fallbackModel) {
                Log::info("Gemini {$model} gagal (HTTP {$response->status()}), mencoba fallback ke " . $fallbackModel);
                $fallbackUrl = self::GEMINI_API_URL . '/' . $fallbackModel . ':generateContent?key=' . urlencode($apiKey);
                $fallbackResponse = Http::timeout(40)->post($fallbackUrl, [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt],
                                [
                                    'inline_data' => [
                                        'mime_type' => $mimeType,
                                        'data' => $base64Image,
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.1,
                        'responseMimeType' => 'application/json',
                    ],
                ]);

                if ($fallbackResponse->successful()) {
                    $response = $fallbackResponse;
                }
            }

            if (!$response->successful()) {
                $err = $response->json();
                $msg = $err['error']['message'] ?? ('HTTP ' . $response->status());
                Log::warning("Gemini AI scan error untuk owner [{$ownerId}]: {$msg}");
                return [
                    'success' => false,
                    'error_code' => 'AI_UPSTREAM_ERROR',
                    'message' => 'Asisten pembaca nota merespon: ' . $msg,
                ];
            }

            $data = $response->json();
            $candidates = $data['candidates'] ?? [];
            if (empty($candidates)) {
                return [
                    'success' => false,
                    'error_code' => 'NO_CONTENT',
                    'message' => 'AI tidak menemukan teks atau isi pada gambar nota yang diunggah.',
                ];
            }

            $rawJsonText = $candidates[0]['content']['parts'][0]['text'] ?? '';
            $parsedOutput = json_decode($rawJsonText, true);

            if (!is_array($parsedOutput)) {
                return [
                    'success' => true,
                    'raw_text' => trim($rawJsonText),
                    'items' => [],
                    'receipt_meta' => null,
                ];
            }

            $items = $parsedOutput['items'] ?? [];
            $receiptMeta = $parsedOutput['receipt_meta'] ?? null;

            // Generate raw_text per baris
            $lines = [];
            foreach ($items as $item) {
                $name = trim($item['name'] ?? '');
                $qty = $item['qty'] ?? 1;
                $unit = trim($item['unit'] ?? '');
                if (empty($name)) continue;

                if (!empty($unit) && !in_array(strtolower($unit), ['pcs', 'biji', 'buah'])) {
                    $lines[] = "{$name} {$qty} {$unit}";
                } else {
                    $lines[] = "{$name} {$qty}";
                }
            }

            return [
                'success' => true,
                'raw_text' => implode("\n", $lines),
                'items' => $items,
                'receipt_meta' => $receiptMeta,
            ];

        } catch (\Throwable $e) {
            Log::error("Exception saat scan nota untuk owner [{$ownerId}]: " . $e->getMessage());
            return [
                'success' => false,
                'error_code' => 'AI_TIMEOUT_OR_NETWORK',
                'message' => 'Gagal memproses gambar nota (Koneksi timeout atau terputus): ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Bangun system prompt khusus untuk nota sparepart dan retail handphone.
     */
    protected static function buildSystemPrompt(string $mode = 'purchase'): string
    {
        return <<<PROMPT
Anda adalah asisten AI kasir dan OCR cerdas spesialis membaca nota manual, bon tulisan tangan, faktur distributor sparepart HP / elektronik, struk belanja, dan surat jalan di Indonesia.

Domain Usaha: Toko Penjualan Sparepart Handphone, Servis HP, dan Aksesoris Gadget di Indonesia.
Peka terhadap istilah sparepart umum: LCD (OLED, Incell, Crown, OG, Diamond), Baterai (Rakkipanda, Hippo, Original), Touchscreen, Flexible (on/off, volume, cas, board), Papan Cas / Sub Board, IC Power, Kamera, Speaker, Buzzer, Backdoor / Casing, Lem T7000/B7000, Obeng, Kaca Touchscreen, Tempered Glass, Charger, Kabel Data, dll.

Tujuan: PEMBELIAN / RESTOK STOK DARI SUPPLIER.
Ekstrak daftar barang/sparepart yang dibeli beserta harga beli satuan (harga modal), jumlah kuantitas (qty), satuan, dan total jika ada.

ATURAN EKSTRAKSI DATA:
1. Baca teks cetak maupun tulisan tangan (handwriting) dengan teliti. Normalisasi salah ketik atau singkatan umum (contoh: "lcd rm c11 crw" -> "LCD Realme C11 Crown", "btre ip 11" -> "Baterai iPhone 11").
2. HANYA ekstrak baris barang yang dibeli / dipesan.
3. JANGAN masukkan header nota (seperti nama toko asal nota, alamat, nomor telepon), footer (seperti ucapan terima kasih), nomor rekening bank, tanda tangan, atau catatan hutang pribadi.
4. Jika jumlah (qty) tidak tertulis eksplisit di baris barang, asumsikan qty = 1.
5. Jika ada harga satuan, masukkan ke "price". Jika hanya ada total per baris dan qty > 1, hitung harga satuan = subtotal / qty.
6. Format output WAJIB berupa JSON murni dengan skema berikut:
{
  "receipt_meta": {
    "date": "YYYY-MM-DD atau null jika tidak ada",
    "receipt_number": "nomor faktur / nota jika ada atau null",
    "total_amount": 0
  },
  "items": [
    {
      "name": "Nama Sparepart / Produk Lengkap yang sudah dinormalisasi",
      "qty": 1.0,
      "unit": "pcs",
      "price": 0,
      "subtotal": 0
    }
  ]
}
JANGAN tambahkan markdown code fences (seperti ```json). Berikan JSON murni valid.
PROMPT;
    }
}
