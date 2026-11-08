<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AiReceiptScannerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AiReceiptController extends Controller
{
    public function __construct()
    {
        if (function_exists('opcache_invalidate')) {
            @opcache_invalidate(app_path('Services/AiReceiptScannerService.php'), true);
            @opcache_invalidate(__FILE__, true);
        }
    }

    /**
     * Dapatkan ID Owner dari user yang sedang login.
     */
    private function getOwnerId(): int
    {
        $user = Auth::user();
        if ($user->userDetail && $user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail ? ($user->userDetail->id_upline ?: $user->id) : $user->id;
    }

    /**
     * Pastikan hanya Owner yang bisa mengedit konfigurasi AI.
     */
    private function ensureManagerAccess()
    {
        $user = Auth::user();
        if (!$user || ($user->userDetail && $user->userDetail->jabatan != '1')) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Pemilik Toko (Owner) yang memiliki izin untuk mengubah konfigurasi AI Toko.'
            ], 403);
        }
        return null;
    }

    /**
     * POST /api/ai/scan-receipt
     * Scan foto nota / faktur / bon menggunakan AI Vision.
     */
    public function scan(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,webp|max:10240', // Max 10MB
            'mode' => 'nullable|string|in:sales,purchase',
        ], [
            'image.required' => 'Foto nota wajib diunggah.',
            'image.image' => 'File yang diunggah harus berupa gambar.',
            'image.mimes' => 'Format foto nota harus jpeg, png, jpg, atau webp.',
            'image.max' => 'Ukuran foto nota tidak boleh lebih dari 10MB.',
        ]);

        $ownerId = $this->getOwnerId();
        $mode = $request->input('mode', 'purchase');

        $result = AiReceiptScannerService::scanReceipt($ownerId, $request->file('image'), $mode);

        if (!$result['success']) {
            $errorCode = $result['error_code'] ?? 'AI_SCAN_FAILED';
            $statusCode = 400;
            if ($errorCode === 'AI_KEY_NOT_CONFIGURED') {
                $statusCode = 422;
            }

            return response()->json([
                'success' => false,
                'error_code' => $errorCode,
                'message' => $result['message'],
            ], $statusCode);
        }

        return response()->json([
            'success' => true,
            'message' => 'Nota berhasil dianalisa oleh AI',
            'data' => [
                'raw_text' => $result['raw_text'],
                'items' => $result['items'],
                'receipt_meta' => $result['receipt_meta'],
            ],
        ]);
    }

    /**
     * GET /api/ai/config
     * Ambil status konfigurasi AI milik toko saat ini.
     */
    public function getConfig(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $config = AiReceiptScannerService::getConfig($ownerId);

        return response()->json([
            'success' => true,
            'data' => $config,
        ]);
    }

    /**
     * POST /api/ai/config
     * Simpan konfigurasi AI (Kunci API, model). Hanya untuk Owner.
     */
    public function saveConfig(Request $request)
    {
        $forbidden = $this->ensureManagerAccess();
        if ($forbidden) return $forbidden;

        $validated = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'is_enabled' => 'nullable|boolean',
            'model' => 'nullable|string|max:100',
        ]);

        $ownerId = $this->getOwnerId();

        // Jika memasukkan API key baru, uji koneksi dulu
        if (!empty($validated['api_key'])) {
            $test = AiReceiptScannerService::testConnection(
                $ownerId,
                $validated['api_key'],
                $validated['model'] ?? null
            );
            if (!$test['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'API Key tidak valid: ' . $test['message'],
                ], 422);
            }
        }

        $config = AiReceiptScannerService::saveConfig($ownerId, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan Asisten AI Toko berhasil disimpan.',
            'data' => $config,
        ]);
    }

    /**
     * POST /api/ai/test-connection
     * Uji koneksi API Key Google Gemini.
     */
    public function testConnection(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:100',
        ]);

        $ownerId = $this->getOwnerId();
        $result = AiReceiptScannerService::testConnection(
            $ownerId,
            $validated['api_key'] ?? null,
            $validated['model'] ?? null
        );

        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => $result['message'],
            'active_model' => $result['active_model'] ?? null,
        ]);
    }
}
