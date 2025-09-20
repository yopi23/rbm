<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TokoSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

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
}
