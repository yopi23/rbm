<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Hutang;
use App\Models\Pembelian;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HutangApiController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId(): int
    {
        $user = Auth::user();
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }

    public function index()
    {
        try {
            $hutang = Hutang::where('kode_owner', $this->getOwnerId())
                ->where('status', 'Belum Lunas')
                ->with('supplier') // PERUBAHAN 1: Memastikan data supplier ikut terambil
                ->orderBy('tgl_jatuh_tempo', 'asc')
                ->get();

            // PERUBAHAN 2: Mengembalikan response dalam format JSON
            return response()->json([
                'success' => true,
                'hutang' => $hutang
            ]);

        } catch (\Exception $e) {
            Log::error('Gagal mengambil data hutang: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data hutang.'
            ], 500); // Kode 500 untuk server error
        }
    }

    public function bayar(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $hutang = Hutang::with('pembelian')->findOrFail($id); // Load relasi pembelian

            // Cek jika sudah lunas untuk mencegah pembayaran ganda
            if ($hutang->status === 'Lunas') {
                 return response()->json([
                    'success' => false,
                    'message' => 'Hutang ini sudah dilunasi sebelumnya.'
                ], 422); // 422 Unprocessable Entity
            }

            // 1. Catat pengeluaran di kas perusahaan
            // Menggunakan $hutang sebagai source agar terdata sebagai "Pembayaran Hutang" di laporan
            $this->catatKas(
                $hutang, 
                0,
                $hutang->total_hutang,
                'Pembayaran Hutang #' . $hutang->kode_nota,
                now()
            );

            // 2. Update status hutang & pembelian
            $hutang->update(['status' => 'Lunas']);
            if ($hutang->pembelian) {
                $hutang->pembelian->update(['status_pembayaran' => 'Lunas']);
            }

            DB::commit();

            // PERUBAHAN 3: Mengembalikan response sukses dalam format JSON
            return response()->json([
                'success' => true,
                'message' => 'Hutang berhasil dibayar.'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Gagal membayar hutang #' . $id . ': ' . $e->getMessage());

            // PERUBAHAN 4: Mengembalikan response error dalam format JSON
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(), // tampilkan pesan asli
            ], 500);
        }
    }
}
