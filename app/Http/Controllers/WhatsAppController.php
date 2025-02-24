<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WhatsAppController extends Controller
{
    private $serverUrl = 'http://localhost:3000/api/whatsapp';

    public function checkStatus()
    {
        $response = Http::get("{$this->serverUrl}/status");
        return response()->json($response->json());
    }

    public function startSession()
    {
        $response = Http::post("{$this->serverUrl}/start");
        return response()->json($response->json());
    }

    public function sendMessage(Request $request)
    {
        $validated = $request->validate([
            'number' => 'required',
            'message' => 'required'
        ]);

        $response = Http::post("{$this->serverUrl}/send", $validated);
        return response()->json($response->json());
    }

    public function logout()
    {
        $response = Http::post("{$this->serverUrl}/logout");
        return response()->json($response->json());
    }

    public function forceDisconnect()
    {
        $response = Http::post("{$this->serverUrl}/force-disconnect");
        return response()->json($response->json());
    }
}
