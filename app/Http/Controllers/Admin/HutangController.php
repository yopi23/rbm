<?php
namespace App\Http\Controllers\Admin;
use App\Http\Controllers\Controller;
use App\Models\Hutang;
use App\Models\Pembelian;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HutangController extends Controller
{
    use ManajemenKasTrait;
    private function getOwnerId(): int
    {
        $user = Auth::user();
        \Log::debug('User object:', [
        'user_id' => $user ? $user->id : null,
        'has_userDetail_method' => $user ? method_exists($user, 'userDetail') : false,
        'userDetail_relation' => $user ? $user->userDetail : null,
        'userDetail_loaded' => $user && $user->relationLoaded('userDetail')
    ]);
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    public function index()
    {
        $page = "Manajemen Hutang Supplier";
        $hutang = Hutang::where('kode_owner', $this->getOwnerId())
                    ->where('status', 'Belum Lunas')
                    ->orderBy('tgl_jatuh_tempo', 'asc')
                    ->get();

        $content = view('admin.page.hutang.index', compact('page', 'hutang'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    public function bayar(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hutang = Hutang::findOrFail($id);
            $pembelian = Pembelian::where('kode_pembelian', $hutang->kode_nota)->first();

            // 1. Catat pengeluaran di kas perusahaan SAAT HUTANG DIBAYAR
            $this->catatKas(
                $hutang, // Updated: Gunakan model Hutang agar konsisten dengan API & Laporan
                0,
                $hutang->total_hutang,
                'Pembayaran Hutang #' . $hutang->kode_nota,
                now()
            );

            // 2. Update status hutang & pembelian
            $hutang->update(['status' => 'Lunas']);
            if ($pembelian) {
                $pembelian->update(['status_pembayaran' => 'Lunas']);
            }

            DB::commit();
            return redirect()->route('hutang.index')->with('success', 'Hutang berhasil dibayar.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Gagal membayar hutang: ' . $e->getMessage()]);
        }
    }
}
