<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TokoSetting;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB; // Tambahkan ini
use Illuminate\Support\Facades\Auth; // Tambahkan ini
use Illuminate\Support\Str;

class TokoSettingController extends Controller
{
    // Mengambil pengaturan untuk owner yang sedang login
    public function getSettings(Request $request)
    {
        $user = $this->getThisUser(); // Menggunakan helper method yang sudah ada
        $settings = TokoSetting::where('id_owner', $user->id_upline)->first();

        if (!$settings) {
            return response()->json(['success' => true, 'data' => null, 'message' => 'Pengaturan belum ada.']);
        }

        // Pastikan logo_url adalah URL lengkap jika ada
        if ($settings->logo_url) {
            $settings->logo_url = Storage::url($settings->logo_url);
        }
        if ($settings->logo_thermal_url) {
            $settings->logo_thermal_url = Storage::url($settings->logo_thermal_url);
        }

        return response()->json(['success' => true, 'data' => $settings]);
    }

    // Menyimpan atau memperbarui pengaturan
    public function updateSettings(Request $request)
    {
        $user = $this->getThisUser();

        $request->validate([
            'nama_toko' => 'nullable|string|max:255',
            'alamat_toko' => 'nullable|string',
            'nomor_cs' => 'nullable|string|max:20',
            'nomor_info_bot' => 'nullable|string|max:20',
            'nota_footer_line1' => 'nullable|string',
            'nota_footer_line2' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024', // Max 1MB
            'print_logo_on_receipt' => 'nullable|boolean',
        ]);

        $data = $request->except('logo');
        $logoPath = null;

        if ($request->hasFile('logo')) {
            // Hapus logo lama jika ada
            $oldSettings = TokoSetting::where('id_owner', $user->id_upline)->first();
            if ($oldSettings && $oldSettings->logo_url) {
                Storage::delete('public/' . $oldSettings->logo_url);
            }

            // Simpan logo baru
            $logoPath = $request->file('logo')->store('public/logos');
            $data['logo_url'] = str_replace('public/', '', $logoPath);

            // Generate Thermal Logo
            try {
                $thermalPath = ThermalPrinterService::generateThermalLogo($data['logo_url']);
                if ($thermalPath) {
                    $data['logo_thermal_url'] = $thermalPath;
                }
            } catch (\Exception $e) {
                 \Illuminate\Support\Facades\Log::error("Thermal logo gen error API: " . $e->getMessage());
            }
        }

        $settings = TokoSetting::updateOrCreate(
            ['id_owner' => $user->id_upline],
            $data
        );

        if ($settings->logo_url) {
            $settings->logo_url = Storage::url($settings->logo_url);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengaturan berhasil disimpan.',
            'data' => $settings
        ], 200);
    }

    // =========================================================
    // BARU: Pengaturan QRIS
    // =========================================================

    /**
     * Ambil data pengaturan QRIS (hanya untuk owner)
     */
    public function getQrisSetting()
    {
        $user = $this->getThisUser(); // user detail yang sedang login

        // Pastikan user yang login adalah owner (id_upline-nya sendiri atau null)
        $ownerDetailId = $user->id_upline ?? $user->id;

        $owner = DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->first();

        if (!$owner) {
             return response()->json(['message' => 'Owner detail not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'qris_payload'      => $owner->qris_payload,
                'qris_display_name' => $owner->qris_display_name,
                'macrodroid_secret' => $owner->macrodroid_secret,
                'owner_detail_id'   => $owner->id,
            ]
        ]);
    }

    /**
     * Simpan/Update data pengaturan QRIS (hanya untuk owner)
     */
    public function updateQrisSetting(Request $request)
    {
        $user = $this->getThisUser(); // user detail yang sedang login

        $validated = $request->validate([
            'qris_payload'      => 'nullable|string|max:10000', // Payload bisa besar (base64)
            'qris_display_name' => 'nullable|string|max:100',
        ]);

        // Pastikan user yang login adalah owner (id_upline-nya sendiri atau null)
        $ownerDetailId = $user->id_upline ?? $user->id;

        $owner = DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->first();

        if (!$owner) {
             return response()->json(['message' => 'Owner detail not found'], 404);
        }

        // Pastikan secret key ada. Jika tidak ada, generate yang baru.
        $secret = $owner->macrodroid_secret ?? Str::random(64);

        DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->update([
                'qris_payload'      => $validated['qris_payload'],
                'qris_display_name' => $validated['qris_display_name'],
                'macrodroid_secret' => $secret,
                'updated_at'        => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan QRIS berhasil disimpan.',
            'data' => [
                'macrodroid_secret' => $secret, // Kembalikan secret yang baru/lama
            ]
        ]);
    }

    public function getThisUser()
    {
        $userId = Auth::id();

        // Dalam implementasi nyata, ini harus menggunakan relasi atau model
        $userDetail = DB::table('user_details')
            ->where('kode_user', $userId)
            ->first();

        return $userDetail;
    }

    // =========================================================
    // BARU: Pengaturan Lokasi Absensi (Geolocation)
    // =========================================================

    /**
     * Ambil data pengaturan lokasi absen (hanya untuk owner)
     */
    public function getOfficeLocation()
    {
        $user = $this->getThisUser(); // user detail yang sedang login

        // Pastikan user yang login adalah owner (id_upline-nya sendiri atau null)
        $ownerDetailId = $user->id_upline ?? $user->id;

        $owner = DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->first();

        if (!$owner) {
             return response()->json(['message' => 'Owner detail not found'], 404);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'default_lat'       => $owner->default_lat,
                'default_lon'       => $owner->default_lon,
                'allowed_radius_m'  => $owner->allowed_radius_m ?? 50, // Default 50m
                'owner_detail_id'   => $owner->id,
            ]
        ]);
    }

    /**
     * Simpan/Update data pengaturan lokasi absen (hanya untuk owner)
     */
    public function updateOfficeLocation(Request $request)
    {
        $user = $this->getThisUser(); // user detail yang sedang login

        $validated = $request->validate([
            'default_lat'       => 'required|numeric|between:-90,90',
            'default_lon'       => 'required|numeric|between:-180,180',
            'allowed_radius_m'  => 'required|integer|min:10|max:1000', // Radius min 10m, max 1000m
        ]);

        // Pastikan user yang login adalah owner (id_upline-nya sendiri atau null)
        $ownerDetailId = $user->id_upline ?? $user->id;

        $owner = DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->first();

        if (!$owner) {
             return response()->json(['message' => 'Owner detail not found'], 404);
        }

        DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->update([
                'default_lat'       => $validated['default_lat'],
                'default_lon'       => $validated['default_lon'],
                'allowed_radius_m'  => $validated['allowed_radius_m'],
                'updated_at'        => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Pengaturan Lokasi Usaha berhasil disimpan.',
            'data' => [
                'default_lat'       => $validated['default_lat'],
                'default_lon'       => $validated['default_lon'],
                'allowed_radius_m'  => $validated['allowed_radius_m'],
            ]
        ]);
    }
}
