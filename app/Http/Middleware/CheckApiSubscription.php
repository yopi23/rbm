<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CheckApiSubscription
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Cek apakah ada user yang terotentikasi via token
        if ($request->user()) {

            // Gunakan fungsi yang sama dari model User
            if ($request->user()->hasActiveSubscription()) {
                // Jika langganan aktif, lanjutkan permintaan
                return $next($request);
            }

            // Jika langganan TIDAK aktif, kembalikan response error JSON
            return response()->json([
                'status' => 'error',
                'message' => 'Akses ditolak. Langganan Anda tidak aktif atau telah berakhir.',
            ], 403); // 403 Forbidden: Tahu siapa Anda, tapi Anda tidak punya izin.
        }

        // Jika tidak ada user (token tidak valid), biarkan middleware auth:sanctum yang menanganinya
        return $next($request);
    }
}
