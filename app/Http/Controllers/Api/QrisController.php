<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class QrisController extends Controller
{
    /**
     * Ambil data QRIS untuk user tertentu (karyawan / owner / admin)
     * Endpoint: GET /api/qris-info?kode_user=123
     */
    public function getQrisInfo(Request $request)
    {
        $kodeUser = $request->query('kode_user');

        // Data user yang sedang membuka halaman QRIS
        $currentUser = DB::table('user_details')
            ->where('kode_user', $kodeUser)
            ->first();

        if (!$currentUser) {
            return response()->json([
                'status' => false,
                'message' => 'User tidak ditemukan'
            ], 404);
        }

        // Tentukan siapa owner-nya
        // Jika user adalah owner (tidak punya upline atau upline = dirinya sendiri)
        // Jika user adalah karyawan, ambil data upline-nya
        $ownerDetailId = $currentUser->id_upline ?: $currentUser->id;

        $owner = DB::table('user_details')
            ->where('id', $ownerDetailId)
            ->first();

        if (!$owner) {
            return response()->json([
                'status' => false,
                'message' => 'Owner tidak ditemukan'
            ], 404);
        }

        // Cek apakah owner sudah setup QRIS
        if (!$owner->qris_payload) {
            return response()->json([
                'status' => false,
                'message' => 'QRIS belum diset oleh owner. Silakan hubungi administrator.'
            ], 422);
        }

        return response()->json([
            'status' => true,
            'data' => [
                'owner_detail_id'   => $owner->id,
                'owner_name'        => $owner->fullname ?? 'Owner',
                'kasir_detail_id'   => $currentUser->id,
                'kasir_name'        => $currentUser->fullname ?? 'Kasir',
                'qris_display_name' => $owner->qris_display_name ?? '',
                'qris_payload'      => $owner->qris_payload,
            ]
        ]);
    }

    /**
     * Ambil riwayat mutasi untuk owner & kasir tertentu
     * Endpoint: GET /api/qris-mutations?owner_id=1&kasir_id=2&limit=20
     */
    public function getMutations(Request $request)
    {
        $validated = $request->validate([
            'owner_id' => 'required|integer',
            'kasir_id' => 'required|integer',
            'limit' => 'nullable|integer|max:100',
        ]);

        $limit = $validated['limit'] ?? 20;

        // KEAMANAN: Validasi bahwa user yang request adalah kasir atau owner tersebut
        $userId = auth()->user()->id ?? null;

        if (!$userId) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $userDetail = DB::table('user_details')
            ->where('kode_user', $userId)
            ->first();

        if (!$userDetail) {
            return response()->json(['message' => 'User detail not found'], 404);
        }

        // Cek apakah user adalah owner atau kasir yang diminta
        $isAuthorized = (
            $userDetail->id == $validated['owner_id'] ||
            $userDetail->id == $validated['kasir_id']
        );

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized access'], 403);
        }

       $twoDaysAgo = now()->subDays(2)->format('Y-m-d H:i:s');

        $mutations = DB::table('mutasi_qris')
            ->where('owner_detail_id', $validated['owner_id'])
            // ->where('kasir_detail_id', $validated['kasir_id'])
            // BARU: Kondisi untuk membatasi hanya 2 hari terakhir
            ->where('reported_at', '>=', $twoDaysAgo)
            ->orderBy('reported_at', 'desc')
            ->limit($limit)
            ->get();

        return response()->json([
            'status' => true,
            'data' => $mutations,
        ]);
    }

    /**
     * Update status mutasi (dari 'new' ke 'read')
     * Endpoint: PUT /api/qris-mutations/{id}/mark-read
     */
    public function markAsRead($id)
    {
        $mutasi = DB::table('mutasi_qris')->where('id', $id)->first();

        if (!$mutasi) {
            return response()->json(['message' => 'Mutation not found'], 404);
        }

        // KEAMANAN: Pastikan user yang update adalah kasir atau owner terkait
        $userId = auth()->user()->id ?? null;
        $userDetail = DB::table('user_details')
            ->where('kode_user', $userId)
            ->first();

        if (!$userDetail) {
            return response()->json(['message' => 'User detail not found'], 404);
        }

        $isAuthorized = (
            $userDetail->id == $mutasi->owner_detail_id ||
            $userDetail->id == $mutasi->kasir_detail_id
        );

        if (!$isAuthorized) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        DB::table('mutasi_qris')
            ->where('id', $id)
            ->update([
                'status' => 'read',
                'updated_at' => now(),
            ]);

        return response()->json([
            'status' => true,
            'message' => 'Mutation marked as read'
        ]);
    }
}
