<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DetailCatatanService;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\Garansi;
use App\Models\PresentaseUser;
use App\Models\ProfitPresentase;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\User;
use App\Models\UserDetail;
use Milon\Barcode\Facades\DNS1DFacade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use PDO;

class ServiceApiController extends Controller
{
    // API
    public function getCompletedToday(Request $request)
    {
        try {
            $today = date('Y-m-d');

            // Query untuk mengambil data
            $completedServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                ->where('status_services', 'Selesai')
                ->whereDate('sevices.updated_at', $today)
                ->join('users', 'sevices.id_teknisi', '=', 'users.id')  // Melakukan join dengan tabel users
                ->select('sevices.*', 'users.name as teknisi')
                ->get();

            // Return response JSON
            return response()->json([
                'success' => true,
                'message' => 'Data layanan yang selesai hari ini berhasil diambil.',
                'data' => $completedServices,
            ], 200);
        } catch (\Exception $e) {
            // Return error response jika ada masalah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkServiceStatus(Request $request, $serviceId)
{
    try {
        // Query untuk mengambil status service berdasarkan ID
        $service = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
            ->where('sevices.id', $serviceId)
            ->join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->select('sevices.id as service_id', 'status_services', 'kode_service', 'nama_pelanggan', 'users.name')
            ->first();

        // Cek apakah service ditemukan
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'Service tidak ditemukan.',
            ], 404);
        }

        // Return response dengan status service
        return response()->json([
            'success' => true,
            'message' => 'Status service berhasil diambil.',
            'data' => [
                'id' => $service->service_id,
                'kode_service' => $service->kode_service,
                'nama_pelanggan' => $service->nama_pelanggan,
                'status_services' => $service->status_services,
                'teknisi' => $service->name,
                'is_completed' => $service->status_services != 'Antri'
            ],
        ], 200);

    } catch (\Exception $e) {
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
        ], 500);
    }
}

    public function getCompletedservice(Request $request)
    {
        try {

            // Query untuk mengambil data
            $completedServices = modelServices::where('kode_owner', $this->getThisUser()->id_upline)
                ->where('status_services', 'Selesai')
                ->join('users', 'sevices.id_teknisi', '=', 'users.id')  // Melakukan join dengan tabel users
                ->select('sevices.*', 'users.name as teknisi')
                ->get();

            // Return response JSON
            return response()->json([
                'success' => true,
                'message' => 'Data layanan yang selesai hari ini berhasil diambil.',
                'data' => $completedServices,
            ], 200);
        } catch (\Exception $e) {
            // Return error response jika ada masalah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function allservice(Request $request)
    {
        try {
            // Ambil kode owner dari user yang sedang login
            $kodeOwner = $this->getThisUser()->id_upline;

            // Ambil query pencarian dari request
            $search = $request->input('search');

            // Jika tidak ada kata kunci pencarian, return array kosong
            if (empty($search)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Silakan masukkan kata kunci pencarian.',
                    'data' => [],
                ], 200);
            }

            // Tahun ini dan tahun sebelumnya
            $tahunIni = date('Y');
            $tahunLalu = $tahunIni - 1;

            // Query pencarian berdasarkan nama pelanggan atau tipe unit
            $allServices = modelServices::where('kode_owner', $kodeOwner)
                ->where(function ($query) use ($search) {
                    $query->where('sevices.nama_pelanggan', 'LIKE', "%$search%")
                        ->orWhere('sevices.type_unit', 'LIKE', "%$search%")
                        ->orwhere('kode_service', $search);
                })
                ->whereYear('sevices.created_at', '>=', $tahunLalu) // Filter tahun
                ->join('users', 'sevices.id_teknisi', '=', 'users.id')
                ->select('sevices.*', 'users.name as teknisi')
                ->get();

            // Return response JSON
            return response()->json([
                'success' => true,
                'message' => 'Data layanan ditemukan.',
                'data' => $allServices,
            ], 200);
        } catch (\Exception $e) {
            // Return error response jika ada masalah
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    function cekService(Request $request) {
        $data = modelServices::where('kode_service', $request->q)->first();

        if (!$data) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        $teknisi = $data->id_teknisi ? User::where('id', $data->id_teknisi)->value('name') : '-';
        $garansi = Garansi::where('kode_garansi', $request->q)->where('type_garansi', 'service')->get();
        $detail = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
            ->where('detail_part_services.kode_services', $data->id)
            ->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
        $detail_luar = DetailPartLuarService::where('kode_services', $data->id)->get();

        return response()->json([
            'teknisi' => $teknisi,
            'data' => $data,
            'garansi' => $garansi,
            'detail' => $detail,
            'detail_luar' => $detail_luar,
        ]);
    }

 } // End API
