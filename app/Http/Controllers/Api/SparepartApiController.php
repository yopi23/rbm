<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DetailPartServices;
use App\Models\Sparepart;
use App\Models\DetailPartLuarService;

class SparepartApiController extends Controller
{
    // cari sparepart
    public function searchSparepartToko(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1',
        ]);

        $query = $request->query('query');
        $spareparts = Sparepart::where('nama_sparepart', 'LIKE', "%$query%")
            ->orWhere('kode_sparepart', 'LIKE', "%$query%")
            ->get();

        if ($spareparts->isEmpty()) {
            return response()->json(['message' => 'No spareparts found.'], 404);
        }

        return response()->json($spareparts, 200);
    }
    // Store Sparepart Toko
    public function storeSparepartToko(Request $request)
    {
        $request->validate([
            'kode_services' => 'required|string',
            'kode_sparepart' => 'required|string|exists:spareparts,kode_sparepart',
            'qty_part' => 'required|integer|min:1',
        ]);

        $sparepart = Sparepart::findOrFail($request->kode_sparepart);

        // Cek apakah stok cukup untuk ditambahkan
        if ($sparepart->stok_sparepart < $request->qty_part) {
            return response()->json([
                'message' => 'Stock is not sufficient for this operation.'
            ], 400);
        }

        $cek = DetailPartServices::where([
            ['kode_services', '=', $request->kode_services],
            ['kode_sparepart', '=', $request->kode_sparepart]
        ])->first();

        if ($cek) {
            $qty_baru = $cek->qty_part + $request->qty_part;

            // Validasi stok sebelum pembaruan
            if ($sparepart->stok_sparepart < ($qty_baru - $cek->qty_part)) {
                return response()->json([
                    'message' => 'Stock is not sufficient for this operation.'
                ], 400);
            }

            $cek->update([
                'qty_part' => $qty_baru,
                'user_input' => auth()->user()->id,
            ]);

            $sparepart->update([
                'stok_sparepart' => $sparepart->stok_sparepart - ($qty_baru - $cek->qty_part),
            ]);

            return response()->json(['message' => 'Sparepart updated successfully.'], 200);
        } else {
            // Validasi stok sebelum pembaruan
            if ($sparepart->stok_sparepart < $request->qty_part) {
                return response()->json([
                    'message' => 'Stock is not sufficient for this operation.'
                ], 400);
            }

            DetailPartServices::create([
                'kode_services' => $request->kode_services,
                'kode_sparepart' => $request->kode_sparepart,
                'detail_modal_part_service' => $sparepart->harga_beli,
                'detail_harga_part_service' => $sparepart->harga_jual,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            $sparepart->update([
                'stok_sparepart' => $sparepart->stok_sparepart - $request->qty_part,
            ]);

            return response()->json(['message' => 'Sparepart added successfully.'], 201);
        }
    }

    // Delete Sparepart Toko
    public function deleteSparepartToko($id)
    {
        $data = DetailPartServices::findOrFail($id);

        if ($data) {
            $update_sparepart = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update_sparepart->stok_sparepart + $data->qty_part;

            $update_sparepart->update([
                'stok_sparepart' => $stok_baru,
            ]);

            $data->delete();

            return response()->json(['message' => 'Sparepart deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Sparepart not found.'], 404);
    }

    // Store Sparepart Luar
    public function storeSparepartLuar(Request $request)
    {
        $request->validate([
            'kode_services' => 'required|string',
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        $create = DetailPartLuarService::create([
            'kode_services' => $request->kode_services,
            'nama_part' => $request->nama_part,
            'harga_part' => $request->harga_part,
            'qty_part' => $request->qty_part,
            'user_input' => auth()->user()->id,
        ]);

        return response()->json(['message' => 'Sparepart luar added successfully.'], 201);
    }

    // Update Sparepart Luar
    public function updateSparepartLuar(Request $request, $id)
    {
        $request->validate([
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        $update = DetailPartLuarService::findOrFail($id);

        $update->update([
            'nama_part' => $request->nama_part,
            'harga_part' => $request->harga_part,
            'qty_part' => $request->qty_part,
            'user_input' => auth()->user()->id,
        ]);

        return response()->json(['message' => 'Sparepart luar updated successfully.'], 200);
    }

    // Delete Sparepart Luar
    public function deleteSparepartLuar($id)
    {
        $data = DetailPartLuarService::findOrFail($id);

        if ($data) {
            $data->delete();
            return response()->json(['message' => 'Sparepart luar deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Sparepart not found.'], 404);
    }
}
