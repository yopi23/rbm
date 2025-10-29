<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TokoSetting;
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
}
