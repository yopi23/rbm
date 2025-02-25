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
            'api_key' => 'required|string',
            'number' => 'required|string',
            'message' => 'required|string',
        ]);

        // Find the device by API key
        $device = WhatsappDevice::where('api_key', $validated['api_key'])->first();

        if (!$device) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        // Call the WhatsApp Gateway API to send the message
        $response = Http::post("{$this->apiBaseUrl}/send/{$device->session_id}", [
            'number' => $validated['number'],
            'message' => $validated['message']
        ]);

        if ($response->successful()) {
            return response()->json([
                'status' => true,
                'message' => 'Message sent successfully',
                'data' => $response->json()
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'Failed to send message',
            'error' => $response->json()
        ], $response->status());
    }
}
