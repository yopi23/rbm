<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckSubscription
{
    /**
     * Daftar nama route yang akan dilewati oleh middleware ini.
     */
    protected $except = [
        'login',
        'logout',
        'signout',
        'register',
        'password.*', // Semua route reset password
        'subscriptions.*', // Semua route langganan
        'administrator.*', // Semua route Super Admin (untuk keamanan ekstra)
        'api.webhooks.*', // Semua route webhook
    ];

    public function handle(Request $request, Closure $next)
    {
        // Jika route saat ini ada di dalam daftar pengecualian, langsung lanjutkan.
        foreach ($this->except as $route) {
            if ($request->routeIs($route)) {
                return $next($request);
            }
        }

        // Jika user belum login, biarkan middleware lain yang menangani.
        if (!Auth::check()) {
            return $next($request);
        }

        // Jika user memiliki langganan aktif (termasuk Super Admin), lanjutkan.
        if (Auth::user()->hasActiveSubscription()) {
            return $next($request);
        }

        // Jika tidak, redirect ke halaman langganan dengan pesan error.
        return redirect()->route('subscriptions.index')->with('error', 'Langganan Anda tidak aktif. Silakan perpanjang untuk melanjutkan.');
    }
}
