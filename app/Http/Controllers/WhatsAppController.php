<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    protected $baseUrl;

    public function __construct()
    {
        // Gunakan domain server Anda
        $this->baseUrl = env('WHATSAPP_SERVICE_URL','https://yoyoycell.my.id:3000');
    }

    public function checkStatus()
    {
        try {
            $response = Http::get($this->baseUrl . '/status');
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat terhubung ke WhatsApp service: ' . $e->getMessage()
            ], 500);
        }
    }
    public function logout()
    {
        try {
            $response = Http::post($this->baseUrl . '/logout');
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak dapat terhubung ke WhatsApp service: ' . $e->getMessage()
            ], 500);
        }
    }
// WhatsAppController.php
public function forceDisconnect()
{
    try {
        $response = Http::post($this->baseUrl . '/force-disconnect');
        return $response->json();
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Tidak dapat terhubung ke WhatsApp service: ' . $e->getMessage()
        ], 500);
    }
}
    public function sendMessage(Request $request)
    {
        $request->validate([
            'number' => 'required',
            'message' => 'required'
        ]);

        try {
            $response = Http::post($this->baseUrl . '/send', [
                'number' => $request->number,
                'message' => $request->message
            ]);

            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Gagal mengirim pesan: ' . $e->getMessage()
            ], 500);
        }
    }
}
