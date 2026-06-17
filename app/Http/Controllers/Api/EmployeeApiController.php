<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\Cabang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class EmployeeApiController extends Controller
{
    /**
     * Get list of employees for the authenticated owner
     */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            // Only owner (jabatan == 1) can manage employees
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mengelola karyawan.'
                ], 403);
            }

            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                ->leftJoin('cabangs', 'users.cabang_id', '=', 'cabangs.id')
                ->where('user_details.id_upline', $user->id)
                ->whereIn('user_details.jabatan', [2, 3]) // 2: Kasir, 3: Teknisi
                ->select([
                    'users.id',
                    'users.name',
                    'users.email',
                    'users.cabang_id',
                    'user_details.fullname',
                    'user_details.jabatan',
                    'user_details.status_user',
                    'user_details.no_telp',
                    'user_details.alamat_user',
                    'cabangs.nama_cabang'
                ])
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar karyawan berhasil diambil',
                'data' => $employees
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error get employees: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mengambil data karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created employee
     */
    public function store(Request $request)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat menambahkan karyawan.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:6',
                'jabatan' => 'required|in:2,3', // 2: Kasir, 3: Teknisi
                'cabang_id' => 'nullable|exists:cabangs,id',
                'no_telp' => 'nullable|string|max:20',
                'alamat_user' => 'nullable|string',
                'status_user' => 'nullable|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create User
            $newUser = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'cabang_id' => $request->cabang_id,
            ]);

            // Create User Detail
            $kode_invite = 'INV' . $newUser->id . $request->jabatan . rand(500, 1000);
            
            UserDetail::create([
                'kode_user' => $newUser->id,
                'foto_user' => '-',
                'fullname' => $request->name,
                'alamat_user' => $request->alamat_user ?? '',
                'no_telp' => $request->no_telp ?? '-',
                'jabatan' => $request->jabatan,
                'id_upline' => $user->id,
                'status_user' => $request->status_user ?? '1',
                'kode_invite' => $kode_invite,
                'link_twitter' => '-',
                'link_facebook' => '-',
                'link_instagram' => '-',
                'link_linkedin' => '-',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil ditambahkan',
                'data' => [
                    'id' => $newUser->id,
                    'name' => $newUser->name,
                    'email' => $newUser->email,
                    'cabang_id' => $newUser->cabang_id
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error store employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambah karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update an employee's details
     */
    public function update(Request $request, $id)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat mengedit karyawan.'
                ], 403);
            }

            $targetUser = User::where('id', $id)->first();
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan.'
                ], 404);
            }

            $userDetail = UserDetail::where('kode_user', $id)->first();
            if (!$userDetail || $userDetail->id_upline != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Karyawan ini tidak berada di bawah kepemilikan Anda.'
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255|unique:users,email,' . $id,
                'password' => 'nullable|string|min:6',
                'jabatan' => 'required|in:2,3',
                'cabang_id' => 'nullable|exists:cabangs,id',
                'no_telp' => 'nullable|string|max:20',
                'alamat_user' => 'nullable|string',
                'status_user' => 'required|in:0,1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $updateData = [
                'name' => $request->name,
                'email' => $request->email,
                'cabang_id' => $request->cabang_id,
            ];

            if ($request->filled('password')) {
                $updateData['password'] = Hash::make($request->password);
            }

            $targetUser->update($updateData);

            $userDetail->update([
                'fullname' => $request->name,
                'alamat_user' => $request->alamat_user ?? $userDetail->alamat_user,
                'no_telp' => $request->no_telp ?? $userDetail->no_telp,
                'jabatan' => $request->jabatan,
                'status_user' => $request->status_user,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil diperbarui',
                'data' => [
                    'id' => $targetUser->id,
                    'name' => $targetUser->name,
                    'email' => $targetUser->email,
                    'cabang_id' => $targetUser->cabang_id
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error update employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memperbarui karyawan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete an employee
     */
    public function destroy($id)
    {
        try {
            $user = auth()->user();
            $detail = UserDetail::where('kode_user', $user->id)->first();
            
            if (!$detail || $detail->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Hanya owner yang dapat menghapus karyawan.'
                ], 403);
            }

            $targetUser = User::where('id', $id)->first();
            if (!$targetUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Karyawan tidak ditemukan.'
                ], 404);
            }

            $userDetail = UserDetail::where('kode_user', $id)->first();
            if (!$userDetail || $userDetail->id_upline != $user->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. Karyawan ini tidak berada di bawah kepemilikan Anda.'
                ], 403);
            }

            DB::beginTransaction();

            $userDetail->delete();
            $targetUser->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Karyawan berhasil dihapus'
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error delete employee: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghapus karyawan: ' . $e->getMessage()
            ], 500);
        }
    }
}
