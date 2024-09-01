<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Laci;
use Carbon\Carbon;

class LaciController extends Controller
{
    public function form()
    {
        return view('laci.form');
    }

    public function store(Request $request)
    {
        $request->validate([
            'receh' => 'required|numeric|min:1',
            'real' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $today = Carbon::today()->format('Y-m-d');

        // Cek jika sudah ada entri pada hari ini
        $laciEntry = Laci::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereDate('tanggal', $today)
            ->first();

        if ($laciEntry) {
            // Update data jika entri sudah ada
            $laciEntry->update([
                'receh' => $request->input('receh'),
                'real' => $request->input('real'),
            ]);
        } else {
            // Simpan data baru jika entri belum ada
            Laci::create([
                'user_id' => $user->id,
                'kode_owner' => $this->getThisUser()->id_upline,
                'receh' => $request->input('receh'),
                'real' => $request->input('real'),
                'tanggal' => $today,
            ]);
        }

        return redirect()->route('dashboard')->with('success', 'Laci berhasil diisi atau diperbarui untuk hari ini.');
    }

    public function updatereal(Request $request)
    {
        $request->validate([
            'real' => 'required|numeric',
        ]);


        $today = Carbon::today()->format('Y-m-d');

        // Cek jika data Laci dengan kode_owner dan tanggal hari ini ada
        $laciEntry = Laci::where('kode_owner', $this->getThisUser()->id_upline)
            ->whereDate('tanggal', $today)
            ->first();

        if ($laciEntry) {
            // Pastikan data yang diupdate benar-benar milik kode_owner yang sedang login dan tanggal hari ini

            $laciEntry->update([
                'real' => $request->input('real'),
            ]);
            return redirect()->route('dashboard')->with('success', 'Data Uang berhasil diperbarui.');
        } else {
            // Jika tidak cocok, jangan update dan bisa kirimkan error atau log
            return redirect()->back()->with('error', 'Aksi tidak diizinkan.');
        }
    }
}
