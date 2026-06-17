<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Cabang;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class CabangApiController extends Controller
{
    /**
     * Get list of branches for the authenticated owner
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            $kode_owner = ($detail && $detail->jabatan == '1') ? $user->id : ($detail ? $detail->id_upline : $user->id);

            $query = Cabang::where('kode_owner', $kode_owner);
            
            if ($request->has('is_active')) {
                $query->where('is_active', $request->boolean('is_active'));
            }

            $cabangs = $query->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar cabang berhasil diambil',
                'data' => $cabangs
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get cabang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data cabang'
            ], 500);
        }
    }

    /**
     * Store a newly created branch
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat membuat cabang.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_cabang' => 'required|string|max:255',
                'alamat_cabang' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cabang = Cabang::create([
                'kode_owner' => $user->id,
                'nama_cabang' => $request->nama_cabang,
                'alamat_cabang' => $request->alamat_cabang,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil ditambahkan',
                'data' => $cabang
            ], 201);

        } catch (\Exception $e) {
            Log::error('Error store cabang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambah cabang: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified branch
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mengedit cabang.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_cabang' => 'required|string|max:255',
                'alamat_cabang' => 'nullable|string',
                'is_active' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $cabang = Cabang::where('id', $id)->where('kode_owner', $user->id)->first();

            if (!$cabang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang tidak ditemukan'
                ], 404);
            }

            $cabang->update([
                'nama_cabang' => $request->nama_cabang,
                'alamat_cabang' => $request->alamat_cabang,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $cabang->is_active
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil diperbarui',
                'data' => $cabang
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error update cabang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui cabang'
            ], 500);
        }
    }

    /**
     * Remove or deactivate the specified branch
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat menghapus cabang.'
                ], 403);
            }

            $cabang = Cabang::where('id', $id)->where('kode_owner', $user->id)->first();

            if (!$cabang) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cabang tidak ditemukan'
                ], 404);
            }

            // Instead of hard deleting, we might just set it as inactive to preserve history
            $cabang->update(['is_active' => false]);

            return response()->json([
                'success' => true,
                'message' => 'Cabang berhasil dinonaktifkan',
                'data' => $cabang
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error delete cabang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus cabang'
            ], 500);
        }
    }

    /**
     * Transfer stock from one branch to another
     */
    public function transferStok(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = \App\Models\UserDetail::where('kode_user', $user->id)->first();
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mentransfer stok.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'from_cabang_id' => 'required|integer|exists:cabangs,id',
                'to_cabang_id' => 'required|integer|exists:cabangs,id|different:from_cabang_id',
                'sparepart_id' => 'required|integer|exists:spareparts,id',
                'qty' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $fromCabangId = $request->from_cabang_id;
            $toCabangId = $request->to_cabang_id;
            $sparepartId = $request->sparepart_id;
            $qty = $request->qty;

            $sourceItem = Sparepart::withoutGlobalScope(\App\Scopes\CabangScope::class)
                ->where('id', $sparepartId)
                ->where('cabang_id', $fromCabangId)
                ->first();

            if (!$sourceItem) {
                return response()->json([
                    'success' => false,
                    'message' => 'Barang sumber tidak ditemukan di cabang asal.'
                ], 404);
            }

            if ($sourceItem->stok_sparepart < $qty) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok di cabang asal tidak mencukupi. Stok saat ini: ' . $sourceItem->stok_sparepart
                ], 400);
            }

            \Illuminate\Support\Facades\DB::beginTransaction();

            $sourceItem->logStockChange(
                -$qty, 
                'transfer_out', 
                $toCabangId, 
                'Transfer stok ke Cabang ID: ' . $toCabangId, 
                $user->id
            );

            $targetItem = Sparepart::withoutGlobalScope(\App\Scopes\CabangScope::class)
                ->where('kode_sparepart', $sourceItem->kode_sparepart)
                ->where('cabang_id', $toCabangId)
                ->first();

            if (!$targetItem) {
                $targetItem = Sparepart::create([
                    'kode_sparepart' => $sourceItem->kode_sparepart,
                    'nama_sparepart' => $sourceItem->nama_sparepart,
                    'desc_sparepart' => $sourceItem->desc_sparepart,
                    'foto_sparepart' => $sourceItem->foto_sparepart,
                    'kode_kategori' => $sourceItem->kode_kategori,
                    'kode_sub_kategori' => $sourceItem->kode_sub_kategori,
                    'stok_sparepart' => 0,
                    'harga_beli' => $sourceItem->harga_beli,
                    'harga_jual' => $sourceItem->harga_jual,
                    'harga_ecer' => $sourceItem->harga_ecer,
                    'harga_pasang' => $sourceItem->harga_pasang,
                    'kode_owner' => $sourceItem->kode_owner,
                    'cabang_id' => $toCabangId,
                    'kode_spl' => $sourceItem->kode_spl,
                    'is_active' => $sourceItem->is_active,
                    'is_visible_on_web' => $sourceItem->is_visible_on_web,
                ]);

                $sourceVariants = \App\Models\ProductVariant::where('sparepart_id', $sourceItem->id)->get();
                foreach ($sourceVariants as $var) {
                    \App\Models\ProductVariant::create([
                        'sparepart_id' => $targetItem->id,
                        'sku' => $var->sku,
                        'purchase_price' => $var->purchase_price,
                        'wholesale_price' => $var->wholesale_price,
                        'retail_price' => $var->retail_price,
                        'internal_price' => $var->internal_price,
                        'stock' => 0,
                    ]);
                }
            }

            $targetItem->logStockChange(
                $qty, 
                'transfer_in', 
                $fromCabangId, 
                'Transfer stok masuk dari Cabang ID: ' . $fromCabangId, 
                $user->id
            );

            \Illuminate\Support\Facades\DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Stok berhasil ditransfer.',
                'data' => [
                    'from_cabang_id' => $fromCabangId,
                    'to_cabang_id' => $toCabangId,
                    'sparepart_kode' => $sourceItem->kode_sparepart,
                    'sparepart_nama' => $sourceItem->nama_sparepart,
                    'quantity_transferred' => $qty,
                    'source_new_stock' => $sourceItem->fresh()->stok_sparepart,
                    'target_new_stock' => $targetItem->fresh()->stok_sparepart
                ]
            ], 200);

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            Log::error('Error transfer stok: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mentransfer stok: ' . $e->getMessage()
            ], 500);
        }
    }
}
