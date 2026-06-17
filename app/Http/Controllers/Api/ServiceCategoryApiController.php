<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ServiceCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class ServiceCategoryApiController extends Controller
{
    /**
     * Get list of service categories.
     */
    public function index(Request $request)
    {
        try {
            $user = $this->getThisUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            $kode_owner = $user->id_upline;

            // Fetch categories for this store
            $categories = ServiceCategory::where('kode_owner', $kode_owner)
                ->where('is_active', true)
                ->orderBy('id', 'asc')
                ->get();

            // Seed default categories if empty
            if ($categories->isEmpty()) {
                DB::beginTransaction();
                try {
                    $defaultCategories = [
                        [
                            'nama' => 'Ringan',
                            'persentase' => 30,
                            'kode_warna' => '#4CAF50',
                            'keywords' => 'lcd, screen, baterai, battery, casing, backdoor, backcover, lens, kamera, camera, speaker, buzzer, flexibel, con, konektor, connector, tombol, button, onoff, volume',
                            'is_default' => true,
                            'is_active' => true,
                            'kode_owner' => $kode_owner,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'nama' => 'Sedang',
                            'persentase' => 40,
                            'kode_warna' => '#FF9800',
                            'keywords' => 'touchscreen, glass, software, flash, bypass, unlock, frp, root, charging port, lampu, backlight, ic',
                            'is_default' => false,
                            'is_active' => true,
                            'kode_owner' => $kode_owner,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                        [
                            'nama' => 'Berat',
                            'persentase' => 50,
                            'kode_warna' => '#F44336',
                            'keywords' => 'mati total, matot, short, cpu, ram, emmc, ufs, reball, mesin, motherboard, jumper, signal, rf, audio, wifi, baseband, power',
                            'is_default' => false,
                            'is_active' => true,
                            'kode_owner' => $kode_owner,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ],
                    ];

                    foreach ($defaultCategories as $catData) {
                        ServiceCategory::create($catData);
                    }

                    DB::commit();

                    $categories = ServiceCategory::where('kode_owner', $kode_owner)
                        ->where('is_active', true)
                        ->orderBy('id', 'asc')
                        ->get();
                } catch (\Exception $e) {
                    DB::rollBack();
                    throw $e;
                }
            }

            return response()->json([
                'success' => true,
                'data' => $categories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store new service category.
     */
    public function store(Request $request)
    {
        try {
            $user = $this->getThisUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            // check if admin (jabatan 1)
            if ($user->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Hanya Admin/Owner yang dapat mengelola kategori.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:100',
                'persentase' => 'required|integer|min:0|max:100',
                'kode_warna' => 'nullable|string|max:7',
                'is_default' => 'nullable|boolean',
                'keywords' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $kode_owner = $user->id_upline;
            $is_default = $request->boolean('is_default', false);

            DB::beginTransaction();

            if ($is_default) {
                // reset other defaults
                ServiceCategory::where('kode_owner', $kode_owner)->update(['is_default' => false]);
            }

            // If it's the first category, make it default
            $count = ServiceCategory::where('kode_owner', $kode_owner)->where('is_active', true)->count();
            if ($count === 0) {
                $is_default = true;
            }

            $category = ServiceCategory::create([
                'nama' => $request->nama,
                'persentase' => $request->persentase,
                'kode_warna' => $request->kode_warna ?? '#4CAF50',
                'is_default' => $is_default,
                'is_active' => true,
                'kode_owner' => $kode_owner,
                'keywords' => $request->keywords,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan.',
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update service category.
     */
    public function update(Request $request, $id)
    {
        try {
            $user = $this->getThisUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($user->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Hanya Admin/Owner yang dapat mengelola kategori.'], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama' => 'required|string|max:100',
                'persentase' => 'required|integer|min:0|max:100',
                'kode_warna' => 'nullable|string|max:7',
                'is_default' => 'nullable|boolean',
                'keywords' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
            }

            $kode_owner = $user->id_upline;
            $category = ServiceCategory::where('kode_owner', $kode_owner)->findOrFail($id);
            $is_default = $request->boolean('is_default', false);

            DB::beginTransaction();

            if ($is_default && !$category->is_default) {
                // reset other defaults
                ServiceCategory::where('kode_owner', $kode_owner)->update(['is_default' => false]);
            }

            $category->update([
                'nama' => $request->nama,
                'persentase' => $request->persentase,
                'kode_warna' => $request->kode_warna ?? $category->kode_warna,
                'is_default' => $is_default,
                'keywords' => $request->keywords,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui.',
                'data' => $category
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Soft delete service category.
     */
    public function destroy($id)
    {
        try {
            $user = $this->getThisUser();
            if (!$user) {
                return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
            }

            if ($user->jabatan != '1') {
                return response()->json(['success' => false, 'message' => 'Hanya Admin/Owner yang dapat mengelola kategori.'], 403);
            }

            $kode_owner = $user->id_upline;
            $category = ServiceCategory::where('kode_owner', $kode_owner)->findOrFail($id);

            if ($category->is_default) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kategori default tidak dapat dihapus. Silakan set kategori lain sebagai default terlebih dahulu.'
                ], 400);
            }

            $category->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Kategori berhasil dihapus.'
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
