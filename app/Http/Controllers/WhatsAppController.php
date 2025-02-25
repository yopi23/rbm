<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\WhatsappDevice;
use Illuminate\Support\Str;

class WhatsAppController extends Controller
{
    protected $apiBaseUrl;

    public function __construct()
    {
        $this->apiBaseUrl = config('services.whatsapp.api_url', 'http://103.196.154.19:3000');
    }

    public function index()
    {
        // Get list of devices from database
        $devices = WhatsappDevice::all();

        return view('whatsapp.index', compact('devices'));
    }

    public function createDevice(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $apiKey = Str::random(32); // Generate unique API key

        // Call the WhatsApp Gateway API to create a new session
        $response = Http::post("{$this->apiBaseUrl}/session/create", [
            'prefix' => 'wa',
        ]);

        if ($response->successful()) {
            $data = $response->json();

            // Save the device details to the database
            $device = WhatsappDevice::create([
                'name' => $validated['name'],
                'session_id' => $data['sessionId'],
                'api_key' => $apiKey,
                'status' => $data['message'],
                'qr_code_endpoint' => $data['qrCodeImage']
            ]);

            return redirect()->route('whatsapp.show', $device->id);
        }

        return back()->withErrors(['error' => 'Failed to create WhatsApp device connection']);
    }

    public function showDevice($id)
    {
        $device = WhatsappDevice::findOrFail($id);

        // Check current status from the API
        $response = Http::get("{$this->apiBaseUrl}/session/check/{$device->session_id}");

        if ($response->successful()) {
            $deviceStatus = $response->json();
            return view('whatsapp.show', compact('device', 'deviceStatus'));
        }

        return view('whatsapp.show', compact('device'))->withErrors(['error' => 'Failed to fetch device status']);
    }

    public function disconnectDevice($id)
    {
        $device = WhatsappDevice::findOrFail($id);

        // Call the API to remove the session
        $response = Http::delete("{$this->apiBaseUrl}/session/remove/{$device->session_id}?removeFiles=true");

        if ($response->successful()) {
            // Delete from our database
            $device->delete();
            return redirect()->route('whatsapp.index')->with('success', 'Device disconnected successfully');
        }

        return back()->withErrors(['error' => 'Failed to disconnect device']);
    }

    public function getQrCode($id)
    {
        $device = WhatsappDevice::findOrFail($id);

        // Proxy the QR code from the API
        $response = Http::get("{$this->apiBaseUrl}/qrcode/{$device->session_id}");

        if ($response->successful()) {
            return response($response->body(), 200)->header('Content-Type', 'image/png');
        }

        return response()->json(['error' => 'QR code not available'], 404);
    }

    public function refreshDeviceStatus()
    {
        $devices = WhatsappDevice::all();
        $updatedDevices = [];

        foreach ($devices as $device) {
            $response = Http::get("{$this->apiBaseUrl}/session/check/{$device->session_id}");

            if ($response->successful()) {
                $status = $response->json();
                $device->update([
                    'status' => $status['session']['status'],
                    'phone_number' => $status['session']['phoneNumber']
                ]);

                $updatedDevices[] = [
                    'id' => $device->id,
                    'name' => $device->name,
                    'status' => $status['session']['status'],
                    'phone_number' => $status['session']['phoneNumber']
                ];
            }
        }

        return response()->json(['devices' => $updatedDevices]);
    }
}
