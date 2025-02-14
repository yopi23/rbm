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
                        ->orWhere('sevices.type_unit', 'LIKE', "%$search%");
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

 } // End API
