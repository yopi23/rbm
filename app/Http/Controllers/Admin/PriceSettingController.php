<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KategoriSparepart;
use App\Models\AttributeValue;
use App\Models\PriceSetting;
use Illuminate\Http\Request;

class PriceSettingController extends Controller
{
    /**
     * Menampilkan halaman untuk mengatur semua harga.
     */
    public function index()
    {
        $page = "Pengaturan Aturan Harga";
        // Ambil semua kategori dan eager load relasi priceSetting agar efisien
        $categories = KategoriSparepart::with('priceSetting')->orderBy('nama_kategori')->get();

        $content = view('admin.page.pengaturan_harga.index', compact('categories', 'page'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function form(Request $request)
    {
        $page = "Atur Harga";
        $setting = null;
        $attributeValue = null;

        // Cek apakah ini untuk aturan harga khusus (berdasarkan nilai atribut)
        if ($request->has('attribute_value_id')) {
            $attributeValue = AttributeValue::with(['attribute.kategori'])->findOrFail($request->attribute_value_id);
            $page = "Atur Harga Khusus untuk: " . $attributeValue->attribute->name . ' - ' . $attributeValue->value;
            // Cari aturan yang sudah ada, atau buat instance baru jika belum ada
            $setting = PriceSetting::where('attribute_value_id', $attributeValue->id)->first() ?? new PriceSetting();
        } else {
            return redirect()->route('price-settings.index')->with('error', 'Kategori atau Atribut tidak ditentukan.');
        }

        $content = view('admin.page.pengaturan_harga.form_khusus', compact('setting', 'attributeValue'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan atau mengupdate semua pengaturan harga.
     */
    public function storeOrUpdate(Request $request)
    {
        // Ambil ID owner yang sedang login
        $ownerId = $this->getThisUser()->id_upline;

        // Bagian ini untuk menangani form harga KHUSUS (per atribut)
        if ($request->has('attribute_value_id')) {
            $request->validate([
                'kategori_sparepart_id' => 'required|exists:kategori_spareparts,id',
                'attribute_value_id' => 'required|exists:attribute_values,id',
            ]);

            PriceSetting::updateOrCreate(
                [
                    // Kunci pencarian spesifik
                    'kategori_sparepart_id' => $request->kategori_sparepart_id,
                    'attribute_value_id' => $request->attribute_value_id,
                    'kode_owner' => $ownerId, // Kunci Owner
                ],
                [
                    // Data untuk diisi/diperbarui
                    'wholesale_margin'     => $request->wholesale_margin ?? 0,
                    'retail_margin'        => $request->retail_margin ?? 0,
                    'internal_margin'      => $request->internal_margin ?? 0,
                    'default_service_fee'  => $request->default_service_fee ?? null,
                    'warranty_percentage'  => $request->warranty_percentage ?? null,
                ]
            );
            $attr_value = \App\Models\AttributeValue::find($request->attribute_value_id);
            return redirect()->route('attributes.edit', $attr_value->attribute_id)->with('success', 'Aturan harga khusus berhasil diperbarui!');

        // Bagian ini untuk menangani form harga UMUM (per kategori)
        } elseif ($request->has('settings')) {
            $request->validate(['settings' => 'required|array']);

            foreach ($request->settings as $categoryId => $setting) {
                PriceSetting::updateOrCreate(
                    [
                        // Kunci pencarian umum
                        'kategori_sparepart_id' => $categoryId,
                        'attribute_value_id' => null,
                        'kode_owner' => $ownerId, // Kunci Owner
                    ],
                    [
                        // Data untuk diisi/diperbarui
                        'wholesale_margin'     => $setting['wholesale_margin'] ?? 0,
                        'retail_margin'        => $setting['retail_margin'] ?? 0,
                        'internal_margin'      => $setting['internal_margin'] ?? 0,
                        'default_service_fee'  => $setting['default_service_fee'] ?? null,
                        'warranty_percentage'  => $setting['warranty_percentage'] ?? null,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Pengaturan harga berhasil diperbarui!');
    }
}
