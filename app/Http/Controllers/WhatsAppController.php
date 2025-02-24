<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private $waApiUrl;

    public function __construct()
    {
        $this->waApiUrl = env('WHATSAPP_API_URL', 'http://localhost:3000');
    }

    public function checkStatus()
    {
        try {
            $response = Http::get($this->waApiUrl . '/status');
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to connect to WhatsApp service'
            ], 500);
        }
    }

    public function sendMessage(Request $request)
    {
        try {
            $request->validate([
                'number' => 'required|string',
                'message' => 'required|string'
            ]);

            $response = Http::post($this->waApiUrl . '/send', [
                'number' => $request->number,
                'message' => $request->message
            ]);

            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to send message'
            ], 500);
        }
    }

    public function logout()
    {
        try {
            $response = Http::post($this->waApiUrl . '/logout');
            return $response->json();
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to logout'
            ], 500);
        }
    }
}
