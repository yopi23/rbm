<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use App\Models\Laci;
use Carbon\Carbon;

class CheckLaci
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        // Periksa jika user tidak terautentikasi
        if (!$user) {
            return redirect()->route('login')->with('error', 'Anda harus login terlebih dahulu.');
        }

        $today = Carbon::today()->toDateString(); // Mengubah ke format Y-m-d

        // Cek apakah kolom receh sudah diisi hari ini
        $isLaciFilledToday = Laci::where('user_id', $user->id)
            ->whereDate('tanggal', $today)
            ->whereNotNull('receh') // Pastikan kolom receh tidak null
            ->exists();

        if (!$isLaciFilledToday) {
            // Redirect ke halaman form pengisian laci jika belum diisi hari ini
            return redirect()->route('laci.form')->with('error', 'Anda harus mengisi laci hari ini terlebih dahulu.');
        }

        return $next($request);
    }
}
