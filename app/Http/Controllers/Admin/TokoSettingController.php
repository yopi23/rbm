<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TokoSetting;
use App\Models\UserDetail;
use App\Services\ThermalPrinterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TokoSettingController extends Controller
{
    /**
     * Get current owner ID
     */
    private function getOwnerId()
    {
        $user = Auth::user();
        $userDetail = UserDetail::where('kode_user', $user->id)->first();

        if ($userDetail->jabatan == '1') {
            return $user->id;
        }

        return $userDetail->id_upline;
    }

    /**
     * Display toko settings page
     */
    public function index()
    {
        $ownerId = $this->getOwnerId();
        $settings = TokoSetting::where('id_owner', $ownerId)->first();

        // Generate public page URL
        $publicPageUrl = null;
        if ($settings && $settings->slug) {
            $publicPageUrl = url('/cek/' . $settings->slug);
        }

        return view('admin.page.toko-settings', [
            'page' => 'Pengaturan Toko',
            'settings' => $settings,
            'publicPageUrl' => $publicPageUrl,
        ]);
    }

    /**
     * Update toko settings
     */
    public function update(Request $request)
    {
        $ownerId = $this->getOwnerId();
        $existingSettings = TokoSetting::where('id_owner', $ownerId)->first();

        $request->validate([
            'nama_toko' => 'nullable|string|max:255',
            'slug' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9-]+$/',
                'unique:toko_settings,slug,' . ($existingSettings->id ?? 'NULL'),
            ],
            'alamat_toko' => 'nullable|string',
            'nomor_cs' => 'nullable|string|max:20',
            'nomor_info_bot' => 'nullable|string|max:20',
            'nota_footer_line1' => 'nullable|string',
            'nota_footer_line2' => 'nullable|string',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg|max:1024',
            'primary_color' => 'nullable|string|max:7',
            'secondary_color' => 'nullable|string|max:7',
            'public_page_enabled' => 'nullable|boolean',
        ], [
            'slug.regex' => 'Slug hanya boleh berisi huruf kecil, angka, dan tanda hubung (-)',
            'slug.unique' => 'Slug sudah digunakan oleh toko lain',
        ]);

        $data = $request->except(['logo', '_token', '_method']);

        // Handle checkbox
        $data['public_page_enabled'] = $request->has('public_page_enabled');
        $data['print_logo_on_receipt'] = $request->has('print_logo_on_receipt');

        // Auto-generate slug if empty but nama_toko is provided
        if (empty($data['slug']) && !empty($data['nama_toko'])) {
            $data['slug'] = TokoSetting::generateUniqueSlug($data['nama_toko']);
        }

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo
            if ($existingSettings && $existingSettings->logo_url) {
                Storage::delete('public/' . $existingSettings->logo_url);
            }

            $logoPath = $request->file('logo')->store('logos', 'public');
            $data['logo_url'] = $logoPath;

            // Generate Thermal Logo
            try {
                $thermalPath = ThermalPrinterService::generateThermalLogo($logoPath);
                if ($thermalPath) {
                    $data['logo_thermal_url'] = $thermalPath;
                }
            } catch (\Exception $e) {
                // Ignore thermal generation error, don't block main flow
                \Illuminate\Support\Facades\Log::error("Thermal logo gen error: " . $e->getMessage());
            }
        }

        TokoSetting::updateOrCreate(
            ['id_owner' => $ownerId],
            $data
        );

        return redirect()->route('toko-settings.index')->with('success', 'Pengaturan toko berhasil disimpan!');
    }

    /**
     * Generate unique slug via AJAX
     */
    public function generateSlug(Request $request)
    {
        $nama = $request->input('nama', '');

        if (empty($nama)) {
            return response()->json(['slug' => '']);
        }

        $slug = TokoSetting::generateUniqueSlug($nama);

        return response()->json(['slug' => $slug]);
    }
}
