<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;


class CheckRole
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        if (!Auth::check()) {
            return redirect()->route('login'); // Arahkan ke halaman login jika belum login
        }

        // Ambil user dan jabatan terkait
        $this_user = User::join('user_details', 'user_details.kode_user', '=', 'users.id')
            ->where('users.id', auth()->user()->id)
            ->first(['users.*', 'user_details.*', 'users.id as id_user']);

        // Cek apakah jabatan pengguna ada di dalam daftar yang diizinkan
        if (in_array($this_user->jabatan, $roles)) {
            return $next($request); // Akses diizinkan, lanjutkan request
        }

        // Jika role tidak cocok, kembalikan response 403 atau redirect
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthorized'], 403);

        }

        return redirect()->route('dashboard')->with('error', 'You are not authorized to access this page.');
    }
}
