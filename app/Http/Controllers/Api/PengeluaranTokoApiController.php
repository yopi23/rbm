<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PengeluaranToko;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use App\Traits\KategoriLaciTrait;
use Illuminate\Support\Facades\Log;
use App\Traits\ManajemenKasTrait;
use App\Models\Shift;

class PengeluaranTokoApiController extends Controller
{
    use KategoriLaciTrait, ManajemenKasTrait;

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
                $query->where(function ($q) use ($search) {
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
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengambil data pengeluaran toko', 'error' => $e->getMessage()], 500);
        }
    }

    public function showPengeluaranToko($id): JsonResponse
    {
        try {
            $data = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);
            return response()->json(['success' => true, 'message' => 'Data pengeluaran toko berhasil diambil', 'data' => $data]);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Data pengeluaran toko tidak ditemukan', 'error' => $e->getMessage()], 404);
        }
    }

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

            $activeShift = Shift::getActiveShift(auth()->user()->id);
            if (!$activeShift) {
                return response()->json(['success' => false, 'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
            }

            $pengeluaran = PengeluaranToko::create([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
                'kode_owner' => $this->getThisUser()->id_upline,
                'shift_id' => $activeShift->id
            ]);

            $this->catatKas($pengeluaran, 0, $pengeluaran->jumlah_pengeluaran, 'Pengeluaran Toko (API): ' . $pengeluaran->nama_pengeluaran, $pengeluaran->tanggal_pengeluaran);

            if ($request->id_kategorilaci) {
                $uangKeluar = (int)str_replace(',', '', $request->jumlah_pengeluaran);
                $keterangan = $request->nama_pengeluaran . " - " . $request->catatan_pengeluaran;
                $this->recordLaciHistory($request->id_kategorilaci, null, $uangKeluar, $keterangan, 'pengeluaran_toko', $pengeluaran->id, 'TKO-' . $pengeluaran->id);
            }

            return response()->json(['success' => true, 'message' => 'Pengeluaran toko berhasil ditambahkan', 'data' => $pengeluaran], 201);
        }
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menambahkan pengeluaran toko', 'error' => $e->getMessage()], 500);
        }
    }

    public function updatePengeluaranToko(Request $request, $id): JsonResponse
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['success' => false, 'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }

        try {
            $request->validate([
                'tanggal_pengeluaran' => 'required|string',
                'nama_pengeluaran' => 'required|string|max:255',
                'jumlah_pengeluaran' => 'required|string',
                'catatan_pengeluaran' => 'required|string',
                'id_kategorilaci' => 'nullable|integer'
            ]);

            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);
            $oldAmount = $pengeluaran->jumlah_pengeluaran;

            $pengeluaran->update([
                'tanggal_pengeluaran' => $request->tanggal_pengeluaran,
                'nama_pengeluaran' => $request->nama_pengeluaran,
                'jumlah_pengeluaran' => $request->jumlah_pengeluaran,
                'catatan_pengeluaran' => $request->catatan_pengeluaran,
            ]);

            if ($request->id_kategorilaci) {
                $newAmount = (int)str_replace(',', '', $request->jumlah_pengeluaran);
                $oldAmountClean = (int)str_replace(',', '', $oldAmount);

                if ($newAmount != $oldAmountClean) {
                    $keterangan = "Update Pengeluaran Toko: " . $request->nama_pengeluaran . " - " . $request->catatan_pengeluaran . " (Penyesuaian dari " . number_format($oldAmountClean) . " ke " . number_format($newAmount) . ")";
                    if ($newAmount > $oldAmountClean) {
                        $this->recordLaciHistory($request->id_kategorilaci, null, $newAmount - $oldAmountClean, $keterangan, 'pengeluaran_toko_update', $pengeluaran->id, 'TKO-UPD-' . $pengeluaran->id);
                    }
                    else {
                        $this->recordLaciHistory($request->id_kategorilaci, $oldAmountClean - $newAmount, null, $keterangan, 'pengeluaran_toko_update', $pengeluaran->id, 'TKO-UPD-' . $pengeluaran->id);
                    }
                }
            }
            return response()->json(['success' => true, 'message' => 'Pengeluaran toko berhasil diupdate', 'data' => $pengeluaran]);
        }
        catch (ValidationException $e) {
            return response()->json(['success' => false, 'message' => 'Validasi gagal', 'errors' => $e->errors()], 422);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal mengupdate pengeluaran toko', 'error' => $e->getMessage()], 500);
        }
    }

    public function deletePengeluaranToko($id): JsonResponse
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['success' => false, 'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }

        try {
            $pengeluaran = PengeluaranToko::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);
            $pengeluaran->delete();
            return response()->json(['success' => true, 'message' => 'Pengeluaran toko berhasil dihapus']);
        }
        catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Gagal menghapus pengeluaran toko', 'error' => $e->getMessage()], 500);
        }
    }
}
