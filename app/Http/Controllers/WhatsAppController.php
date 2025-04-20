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
        $this->apiBaseUrl = config('services.whatsapp.api_url', 'http://127.0.0.1:3000');
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

        // Get the current user's ID to use as owner code
        $ownerCode = $this->getThisUser()->id_upline;

        // Call the WhatsApp Gateway API to create a new session
        $response = Http::post("{$this->apiBaseUrl}/session/create", [
            'prefix' => 'wa',
            'ownerCode' => $ownerCode,
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

                // Update status di database
                $device->update([
                    'status' => $deviceStatus['session']['status'] ?? 'UNKNOWN',
                    'phone_number' => $deviceStatus['session']['phoneNumber'] ?? null
                ]);

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
        }elseif ($response->status() == 404) {
        $device->delete();
        }
        return back()->withErrors(['error' => 'Failed to disconnect device']);
    }

    public function getQrCode($id)
    {
        $device = WhatsappDevice::findOrFail($id);

        // Proxy the QR code from the API
        $response = Http::get("{$this->apiBaseUrl}/session/qrcode/{$device->session_id}");

        if ($response->successful()) {
            return response($response->body(), 200)->header('Content-Type', 'image/png');
        }

        return response()->json(['error' => 'QR code not available'], 404);
    }

    public function refreshDeviceStatus($id)
    {
        // Ambil satu data perangkat berdasarkan id
        $device = WhatsappDevice::findOrFail($id);

        // Panggil API eksternal
        $response = Http::get("{$this->apiBaseUrl}/session/check/{$device->session_id}");

        // Cek apakah request berhasil
        if ($response->successful()) {
            $status = $response->json();
            // Update status di database
            $device->update([
                'status' => $status['session']['status'] ?? 'UNKNOWN',
                'phone_number' => $status['session']['phoneNumber'] ?? null
            ]);
            return response()->json([
                'id' => $device->id,
                'name' => $device->name,
                'status' => $status['session']['status'] ?? 'UNKNOWN',
                'phone_number' => $status['session']['phoneNumber'] ?? null
            ]);
        } else {
            // Jika API memberikan response 404 Not Found, berarti device tidak ada di API
            if ($response->status() === 404) {
                // Hapus device dari database
                $device->delete();

                return response()->json([
                    'id' => $device->id,
                    'name' => $device->name,
                    'status' => 'DELETED',
                    'phone_number' => null,
                    'message' => 'Device telah dihapus karena tidak ditemukan di API'
                ]);
            }
            Log::error("Gagal mengambil data untuk session ID: {$device->session_id}");
            return response()->json(['error' => 'Gagal mengambil data dari API'], 500);
        }
    }

}
