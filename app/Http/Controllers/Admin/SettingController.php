<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SettingController extends Controller
{
    private function getOwnerId()
    {
        $user = Auth::user();
        return ($user->userDetail->jabatan == '1') ? $user->id : $user->userDetail->id_upline;
    }

    /**
     * Menampilkan halaman pengaturan gabungan (Jam & Tombol Manual).
     */
    public function index()
    {
        $page = "Pengaturan & Proses Tutup Buku";
        $ownerId = $this->getOwnerId();
        $setting = DB::table('close_book_setting')->where('kode_owner', $ownerId)->first();

        // Kita tetap menggunakan view ini, tapi isinya akan kita ubah
        $content = view('admin.page.settings.index', compact('page', 'setting'));
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menyimpan pengaturan jam tutup buku.
     */
    public function storeCloseBook(Request $request)
    {
        $request->validate(['jam_tutup_buku' => 'required|date_format:H:i']);
        $ownerId = $this->getOwnerId();

        DB::table('close_book_setting')->updateOrInsert(
            ['kode_owner' => $ownerId],
            ['jam' => $request->jam_tutup_buku . ':00', 'keterangan' => 'Jam Tutup Buku Harian']
        );
        Cache::forget('closing_time_' . $ownerId);
        return redirect()->route('settings.index')->with('success', 'Jam tutup buku harian berhasil disimpan.');
    }
}
