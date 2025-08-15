<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Pengambilan;
use App\Models\Sevices;
use App\Traits\KategoriLaciTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PengambilanController extends Controller
{
    use KategoriLaciTrait;

    public function store(Request $request)
{
    try {
        DB::beginTransaction();

        // Validasi request
        $validator = Validator::make($request->all(), [
            'nama_pengambilan' => 'required|string',
            'tgl_pengambilan' => 'required|date',
            'total_bayar' => 'required|numeric',
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
            ], 409); // 409 Conflict
        }

        // Generate kode pengambilan
        $count = Pengambilan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline]
        ])->count();

        $kode_pengambilan = 'PNG' . date('Ymd') . Auth::id() . $count;

        // Insert ke table pengambilan
        $pengambilan = Pengambilan::create([
            'kode_pengambilan' => $kode_pengambilan,
            'tgl_pengambilan' => $request->tgl_pengambilan,
            'nama_pengambilan' => $request->nama_pengambilan,
            'total_bayar' => $request->total_bayar,
            'user_input' => Auth::id(),
            'status_pengambilan' => '1',
            'kode_owner' => $this->getThisUser()->id_upline,
        ]);

        // Update status services
        Sevices::whereIn('id', $request->service_ids)
            ->update([
                'status_services' => 'Diambil',
                'kode_pengambilan' => $pengambilan->id
            ]);

        // Catat histori laci
        $keterangan = 'Ngambil Unit oleh-' . $request->nama_pengambilan;
        $this->recordLaciHistory(
            $request->id_kategorilaci,
            $request->total_bayar,
            null,
            $keterangan,
            'Pengambilan',
            $pengambilan->id,
            $kode_pengambilan


        );

        // Ambil data service yang terkait
        $services = Sevices::whereIn('id', $request->service_ids)->get();

        DB::commit();

        return response()->json([
            'status' => true,
            'message' => 'Pengambilan berhasil dibuat',
            'data' => [
                'pengambilan' => $pengambilan,
                'services' => $services
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
