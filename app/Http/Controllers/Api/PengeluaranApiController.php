<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\User;
use App\Models\UserDetail;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Traits\KategoriLaciTrait;
use Illuminate\Support\Facades\Log;

class PengeluaranApiController extends Controller
{
    use KategoriLaciTrait;

    /**
     * Get all pengeluaran toko
     */
    public function getPengeluaranToko(Request $request): JsonResponse
    {
        try {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                              ->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
                      ->orWhere('catatan_pengeluaran', 'LIKE', "%{$search}%");
                });
            }

            $data = $query->latest()->paginate($perPage);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran toko berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pengeluaran toko by ID
     */
    public function showPengeluaranToko($id): JsonResponse
    {
        try {
            $data = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                  ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran toko berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran toko tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store new pengeluaran toko
     */
    public function storePengeluaranToko(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'tanggal_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'jumlah_pengeluaran' => 'required|string',
                'catatan_pengeluaran' => 'required|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            $pengeluaran = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);

            // Handle laci history if kategori laci is provided
            if ($request->id_kategorilaci) {
                $kategoriId = $request->id_kategorilaci;
                $uangKeluar = (int) str_replace(',', '', $request->jumlah_pengeluaran);
                $keterangan = $request->nama_pengeluaran . "-" . $request->catatan_pengeluaran;

                $this->recordLaciHistory($kategoriId, null, $uangKeluar, $keterangan);
            }

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil ditambahkan',
                'data' => $pengeluaran
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update pengeluaran toko
     */
    public function updatePengeluaranToko(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'tanggal_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'jumlah_pengeluaran' => 'required|string',
                'catatan_pengeluaran' => 'required|string'
            ]);

            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                        ->findOrFail($id);

            $pengeluaran->update([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil diupdate',
                'data' => $pengeluaran
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pengeluaran toko
     */
    public function deletePengeluaranToko($id): JsonResponse
    {
        try {
            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)
                                        ->findOrFail($id);

            $pengeluaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran toko berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran toko',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all pengeluaran operasional
     */
    public function getPengeluaranOperasional(Request $request): JsonResponse
    {
        try {
        $perPage = $request->get('per_page', 15);
        $search = $request->get('search');
        $kategori = $request->get('kategori');
        $startDate = $request->get('start_date', date('Y-m-d'));
        $endDate = $request->get('end_date', date('Y-m-d'));

        $query = PengeluaranOperasional::with(['pegawai' => function($q) {
            $q->select('id', 'name');
        }])->where('kode_owner', $this->getThisUser()->id_upline)
          ->whereBetween('tgl_pengeluaran', [$startDate, $endDate]);

            if ($search) {
                $query->where(function($q) use ($search) {
                    $q->where('nama_pengeluaran', 'LIKE', "%{$search}%")
                      ->orWhere('desc_pengeluaran', 'LIKE', "%{$search}%");
                });
            }

            if ($kategori) {
                $query->where('kategori', $kategori);
            }

            $data = $query->latest('tgl_pengeluaran')->paginate($perPage);

            // Transform data to include employee name for payroll category
            $data->getCollection()->transform(function ($item) {
                if ($item->kategori === 'Penggajian' && $item->pegawai) {
                    $item->kategori_display = $item->kategori . ' (' . $item->pegawai->name . ')';
                } else {
                    $item->kategori_display = $item->kategori;
                }
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran operasional berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data pengeluaran operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single pengeluaran operasional by ID
     */
    public function showPengeluaranOperasional($id): JsonResponse
    {
        try {
            $data = PengeluaranOperasional::with(['pegawai' => function($q) {
                $q->select('id', 'name');
            }])->where('kode_owner', $this->getThisUser()->id_upline)
              ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Data pengeluaran operasional berhasil diambil',
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Data pengeluaran operasional tidak ditemukan',
                'error' => $e->getMessage()
            ], 404);
        }
    }

    /**
     * Store new pengeluaran operasional
     */
    public function storePengeluaranOperasional(Request $request): JsonResponse
{
    try {
        Log::info('Memulai proses storePengeluaranOperasional', ['request' => $request->all()]);

        $request->validate([
            'tgl_pengeluaran' => 'required|string',
            'nama_pengeluaran' => 'required|string|max:255',
            'kategori' => 'required|string|max:100',
            'kode_pegawai' => 'nullable|string',
            'jml_pengeluaran' => 'required|string',
            'desc_pengeluaran' => 'required|string'
        ]);

        Log::debug('Validasi berhasil', ['data' => $request->all()]);

        $pegawai = $this->getThisUser()->kode_user;
        Log::debug('Nilai pegawai yang diproses', ['pegawai' => $pegawai]);

        $pengeluaran = PengeluaranOperasional::create([
            'tgl_pengeluaran' => $request->tgl_pengeluaran,
            'nama_pengeluaran' => $request->nama_pengeluaran,
            'kategori' => $request->kategori,
            'kode_pegawai' => $pegawai,
            'jml_pengeluaran' => $request->jml_pengeluaran,
            'desc_pengeluaran' => $request->desc_pengeluaran ?? '',
            'kode_owner' => $this->getThisUser()->id_upline
        ]);

        Log::info('Pengeluaran berhasil dibuat', ['pengeluaran' => $pengeluaran->toArray()]);

        // Update employee balance if it's payroll
        if ($pegawai && $pegawai !== '-') {
            Log::debug('Memproses update saldo pegawai', ['kode_pegawai' => $pegawai]);

            $pegawaiDetail = UserDetail::where('kode_user', $pegawai)->first();

            if ($pegawaiDetail) {
                $jmlPengeluaran = (int) str_replace(',', '', $request->jml_pengeluaran);
                Log::debug('Detail pegawai ditemukan', [
                    'pegawaiDetail' => $pegawaiDetail->toArray(),
                    'jml_pengeluaran' => $jmlPengeluaran
                ]);

                $pegawaiDetail->update([
                    'saldo' => $pegawaiDetail->saldo + $jmlPengeluaran
                ]);

                Log::info('Saldo pegawai berhasil diupdate', [
                    'kode_pegawai' => $pegawai,
                    'saldo_baru' => $pegawaiDetail->saldo
                ]);
            } else {
                Log::warning('Detail pegawai tidak ditemukan', ['kode_pegawai' => $pegawai]);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengeluaran operasional berhasil ditambahkan',
            'data' => $pengeluaran->load('pegawai')
        ], 201);

    } catch (ValidationException $e) {
        Log::error('Validasi gagal', ['errors' => $e->errors(), 'request' => $request->all()]);
        return response()->json([
            'success' => false,
            'message' => 'Validasi gagal',
            'errors' => $e->errors()
        ], 422);
    } catch (\Exception $e) {
        Log::error('Gagal menambahkan pengeluaran operasional', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'request' => $request->all()
        ]);
        return response()->json([
            'success' => false,
            'message' => 'Gagal menambahkan pengeluaran operasional',
            'error' => $e->getMessage()
        ], 500);
    }
}

    /**
     * Update pengeluaran operasional
     */
    public function updatePengeluaranOperasional(Request $request, $id): JsonResponse
    {
        try {
            $request->validate([
                'tgl_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'kategori' => 'required|string|max:100',
                'kode_pegawai' => 'nullable|string',
                'jml_pengeluaran' => 'required|string',
                'desc_pengeluaran' => 'required|string'
            ]);

            $pengeluaran = PengeluaranOperasional::where('kode_owner', $this->getThisUser()->id_upline)
                                                ->findOrFail($id);

            $pegawai = $request->kategori == 'Penggajian' ? $request->kode_pegawai : null;

            $pengeluaran->update([
                'tgl_pengeluaran' => $request->tgl_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'kategori' => $request->kategori,
                'kode_pegawai' => $pegawai,
                'jml_pengeluaran' => $request->jml_pengeluaran,
                'desc_pengeluaran' => $request->desc_pengeluaran ?? '',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran operasional berhasil diupdate',
                'data' => $pengeluaran->load('pegawai')
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate pengeluaran operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete pengeluaran operasional
     */
    public function deletePengeluaranOperasional($id): JsonResponse
    {
        try {
            $pengeluaran = PengeluaranOperasional::where('kode_owner', $this->getThisUser()->id_upline)
                                                ->findOrFail($id);

            $pengeluaran->delete();

            return response()->json([
                'success' => true,
                'message' => 'Pengeluaran operasional berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus pengeluaran operasional',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get employees for dropdown
     */
    public function getEmployees(): JsonResponse
    {
        try {
            $employees = User::join('user_details', 'users.id', '=', 'user_details.kode_user')
                           ->where([
                               ['user_details.jabatan', '!=', '0'],
                               ['user_details.jabatan', '!=', '1'],
                               ['user_details.status_user', '=', '1'],
                               ['user_details.id_upline', '=', $this->getThisUser()->id_upline]
                           ])
                           ->select('users.id', 'users.name')
                           ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data karyawan berhasil diambil',
                'data' => $employees
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data karyawan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get summary statistics
     */
    public function getSummary(Request $request): JsonResponse
    {
        try {
            Log::info('Memulai proses getSummary', ['request' => $request->all()]);

            $startDate = $request->get('start_date');
            $endDate = $request->get('end_date');
            $ownerId = $this->getThisUser()->id_upline;

            Log::debug('Parameter dan user data', [
                'start_date' => $startDate,
                'end_date' => $endDate,
                'owner_id' => $ownerId,
                'user' => $this->getThisUser()->toArray()
            ]);

            // Validasi format tanggal
        if (!strtotime($startDate) || !strtotime($endDate)) {
            return response()->json([
                'success' => false,
                'message' => 'Format tanggal tidak valid'
            ], 400);
        }

        // Pastikan start date tidak lebih besar dari end date
        if ($startDate > $endDate) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai tidak boleh lebih besar dari tanggal akhir'
            ], 400);
        }

            // Query untuk Pengeluaran Toko
            $tokoQuery = PengeluaranToko::where('kode_owner', $ownerId)
            ->whereBetween('tanggal_pengeluaran', [$startDate, $endDate]);

            Log::debug('Query Pengeluaran Toko', [
                'sql' => $tokoQuery->toSql(),
                'bindings' => $tokoQuery->getBindings()
            ]);

            // Query untuk Pengeluaran Operasional
            $opexQuery = PengeluaranOperasional::where('kode_owner', $ownerId)
            ->whereBetween('tgl_pengeluaran', [$startDate, $endDate]);

            Log::debug('Query Pengeluaran Operasional', [
                'sql' => $opexQuery->toSql(),
                'bindings' => $opexQuery->getBindings()
            ]);

            $totalToko = $tokoQuery->sum('jumlah_pengeluaran') ?? 0;
            $totalOpex = $opexQuery->sum('jml_pengeluaran') ?? 0;
            $totalPengeluaran = $totalToko + $totalOpex;

            $countToko = $tokoQuery->count();
            $countOpex = $opexQuery->count();

            Log::debug('Hasil perhitungan summary', [
                'total_toko' => $totalToko,
                'total_opex' => $totalOpex,
                'count_toko' => $countToko,
                'count_opex' => $countOpex
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Summary berhasil diambil',
                'data' => [
                    'total_pengeluaran_toko' => $totalToko,
                    'total_pengeluaran_operasional' => $totalOpex,
                    'total_semua_pengeluaran' => $totalPengeluaran,
                    'jumlah_transaksi_toko' => $countToko,
                    'jumlah_transaksi_operasional' => $countOpex,
                    'periode' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Error pada getSummary', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil summary',
                'error' => $e->getMessage()
            ], 500);
        }
    }

}
