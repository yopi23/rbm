<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengambilan;
use App\Models\Sevices;
use App\Models\Shift;
use App\Models\kas_perusahaan;
use App\Traits\KategoriLaciTrait;
use App\Traits\ManajemenKasTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengambilanController extends Controller
{
    use KategoriLaciTrait;
    use ManajemenKasTrait;

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            // Validasi request
            $validator = Validator::make($request->all(), [
                'nama_pengambilan' => 'required|string',
                'tgl_pengambilan' => 'required|date',
                'id_kategorilaci' => 'required|exists:kategori_lacis,id',
                'service_ids' => 'required|array',
                'service_ids.*' => 'exists:sevices,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Cek apakah ada service yang sudah diambil
            $alreadyTaken = Sevices::whereIn('id', $request->service_ids)
                ->where('status_services', 'Diambil')
                ->get();



            if ($alreadyTaken->count() > 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'Beberapa unit sudah diambil sebelumnya',
                    'data' => $alreadyTaken
                ], 409);
            }

            // Ambil data services dan hitung total
            $services = Sevices::whereIn('id', $request->service_ids)->get();

            // Hitung total dari harga services
            $total_services = $services->sum('total_biaya'); // asumsi kolom harga ada di table sevices

            // Kurangi dengan DP jika ada
            $dp_amount = $services->sum('dp') ?? 0;
            $total_bayar = $total_services - $dp_amount;

            // Pastikan total bayar tidak negatif
            if ($total_bayar < 0) {
                return response()->json([
                    'status' => false,
                    'message' => 'DP tidak boleh lebih besar dari total harga services',
                    'data' => [
                        'total_services' => $total_services,
                        'dp' => $dp_amount
                    ]
                ], 422);
            }

            // Generate kode pengambilan
            $count = Pengambilan::where([
                ['kode_owner', '=', $this->getThisUser()->id_upline]
            ])->count();

            $kode_pengambilan = 'PNG' . date('Ymd') . Auth::id() . $count;

            // Get Active Shift
            $shiftId = null;
            $activeShift = Shift::getActiveShift(Auth::id());
            if ($activeShift) {
                $shiftId = $activeShift->id;
            }

            // Insert ke table pengambilan
            $pengambilan = Pengambilan::create([
                'kode_pengambilan' => $kode_pengambilan,
                'tgl_pengambilan' => $request->tgl_pengambilan,
                'nama_pengambilan' => $request->nama_pengambilan,
                'total_bayar' => $total_bayar, // menggunakan hasil perhitungan
                'total_services' => $total_services, // menyimpan total sebelum dipotong DP
                'dp' => $dp_amount, // menyimpan jumlah DP
                'user_input' => Auth::id(),
                'status_pengambilan' => '1',
                'kode_owner' => $this->getThisUser()->id_upline,
                'shift_id' => $shiftId,
            ]);

            // Update status services
            Sevices::whereIn('id', $request->service_ids)
                ->update([
                    'status_services' => 'Diambil',
                    'kode_pengambilan' => $pengambilan->id
                ]);

            // Catat histori laci
            $keterangan = 'Ngambil Unit oleh-' . $request->nama_pengambilan;
            if ($dp_amount > 0) {
                $keterangan .= ' (Total: ' . number_format($total_services) . ', DP: ' . number_format($dp_amount) . ')';
            }

            if ($total_bayar > 0) {
                $this->catatKas(
                    $pengambilan, // Model sumber
                    $total_bayar, // Debit
                    0, // Kredit
                    'Pelunasan Service API #' . $pengambilan->kode_pengambilan,
                    now()
                );
            }

            $this->recordLaciHistory(
                $request->id_kategorilaci,
                $total_bayar, // yang masuk ke laci adalah setelah dipotong DP
                null,
                $keterangan,
                'Pengambilan',
                $pengambilan->id,
                $kode_pengambilan
            );

            DB::commit();

            return response()->json([
                'status' => true,
                'message' => 'Pengambilan berhasil dibuat',
                'data' => [
                    'pengambilan' => $pengambilan,
                    'services' => $services,
                    'calculation' => [
                        'total_services' => $total_services,
                        'dp_amount' => $dp_amount,
                        'total_bayar' => $total_bayar
                    ]
                ]
            ], 201);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // API untuk mendapatkan daftar service yang tersedia
    public function getAvailableServices()
    {
        try {
            $services = Sevices::where([
                ['status_services', '=', 'Selesai'],
                ['kode_owner', '=', $this->getThisUser()->id_upline],
                ['kode_pengambilan', '=', null] // Hanya yang belum diambil
            ])->get();

            return response()->json([
                'status' => true,
                'data' => $services
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // API untuk mendapatkan daftar kategori laci
    public function getKategoriLaciList()
    {
        try {
            $kategoriLaci = $this->getKategoriLaci();

            return response()->json([
                'status' => true,
                'data' => $kategoriLaci
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => 'Terjadi kesalahan',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
