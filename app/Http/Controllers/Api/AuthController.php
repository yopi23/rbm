<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\SalarySetting;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                // 'version' => 'required', // Tambahkan validasi untuk versi
            ]);

            // Cek versi aplikasi terlebih dahulu
            $clientVersion = '2025.08.19';
            // $clientVersion = $request->input('version');
            $minVersion = '2025.08.19'; // versi minimum yang diizinkan

            if (version_compare($clientVersion, $minVersion, '<')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Versi aplikasi kamu sudah tidak didukung. Silakan update terlebih dahulu.',
                    'force_update' => true
                ], 426); // 426 Upgrade Required
            }

            // Jika versi OK, cek kredensial login
            if (!Auth::attempt($request->only('email', 'password'))) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data Tidak Cocok',
                ], 401);
            }

             $user = Auth::user()->load('userDetail');

            // --- PENGECEKAN STATUS USER ---
            // Cek apakah user detail ada dan statusnya aktif ('1')
            if ($user->userDetail && $user->userDetail->status_user != '1') {
                // Jika tidak aktif, langsung logout dan kirim pesan error
                Auth::logout();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun kamu Belum aktif, Silahkan Hubungi Admin untuk Mengaktifkan Akunmu Kembali',
                ], 403); // 403 Forbidden, karena user terautentikasi tapi tidak diizinkan
            }

            // Login berhasil
            // $user = User::where('email', $request->email)
            //     ->with('userDetail')
            //     ->first();
            $user = User::with(['userDetail', 'salarySetting:id,user_id,compensation_type'])
                ->where('email', $request->email)
                ->first();
            $token = $user->createToken('auth_token')->plainTextToken;


            return response()->json([
                'status' => 'success',
                'message' => 'Login successful',
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
                'subscription_active' => $user->hasActiveSubscription(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    public function validateToken(Request $request)
    {
        try {
            // Ambil versi dari request
            $clientVersion = '2025.08.19';
            // $clientVersion = $request->input('version');
            $minVersion = '2025.08.19'; // versi minimum yang diizinkan

            // Cek versi aplikasi terlebih dahulu, terlepas dari token
            if (version_compare($clientVersion, $minVersion, '<')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Versi aplikasi kamu sudah tidak didukung. Silakan update terlebih dahulu.',
                    'force_update' => true
                ], 426); // 426 Upgrade Required
            }
             $user = Auth::user()->load('userDetail');

            // --- PENGECEKAN STATUS USER ---
            // Cek apakah user detail ada dan statusnya aktif ('1')
            if ($user->userDetail && $user->userDetail->status_user != '1') {
                // Jika tidak aktif, langsung logout dan kirim pesan error
                Auth::logout();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Akun kamu Belum aktif, Silahkan Hubungi Admin untuk Mengaktifkan Akunmu Kembali',
                ], 403); // 403 Forbidden, karena user terautentikasi tapi tidak diizinkan
            }

            // Kemudian cek validitas token
            $user = $request->user();

            if ($user) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Token is valid.',
                    'user' => $user->load('userDetail'),
                    'subscription_active' => $user->hasActiveSubscription(),
                ], 200);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Invalid token.',
                'force_update' => false
            ], 401);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Something went wrong.',
                'error' => $e->getMessage(),
                'force_update' => false
            ], 500);
        }
    }
}
