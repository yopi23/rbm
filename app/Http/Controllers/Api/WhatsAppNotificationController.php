<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\WhatsAppService;
use App\Models\WhatsappDevice;

class WhatsAppNotificationController extends Controller
{
    protected $whatsAppService;

    public function __construct(WhatsAppService $whatsAppService)
    {
        $this->whatsAppService = $whatsAppService;
    }

    /**
     * Send a message via WhatsApp
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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

        $result = $this->whatsAppService->sendMessage(
            $validated['number'],
            $validated['message'],
            $device->session_id
        );

        if ($result['status']) {
            return response()->json($result);
        }

        return response()->json($result, 500);
    }

    /**
     * Send service completion notification
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendServiceCompletionNotification(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'nomor_services' => 'required|string',
            'nama_barang' => 'required|string',
            'no_hp' => 'required|string',
        ]);

        // Find the device by API key
        $device = WhatsappDevice::where('api_key', $validated['api_key'])->first();

        if (!$device) {
            return response()->json([
                'status' => false,
                'message' => 'Invalid API key'
            ], 401);
        }

        $result = $this->whatsAppService->sendServiceCompletionNotification([
            'nomor_services' => $validated['nomor_services'],
            'nama_barang' => $validated['nama_barang'],
            'no_hp' => $validated['no_hp'],
        ]);

        if ($result['status']) {
            return response()->json($result);
        }

        return response()->json($result, 500);
    }
}
