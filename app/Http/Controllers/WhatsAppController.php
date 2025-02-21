<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        $this->baseUrl = env('WHATSAPP_SERVICE_URL', 'http://localhost:3000');
    }

    public function checkStatus()
    {
        try {
            $response = Http::get($this->baseUrl . '/status');
            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Status Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat terhubung ke WhatsApp service'
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'number' => 'required|string',
            'message' => 'required|string'
        ]);

        try {
            $response = Http::post($this->baseUrl . '/send', [
                'number' => $request->number,
                'message' => $request->message
            ]);

            return $response->json();
        } catch (\Exception $e) {
            Log::error('WhatsApp Send Error: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim pesan'
            ], 500);
        }
    }
}
