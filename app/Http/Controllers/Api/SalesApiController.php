<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\Sparepart;
use App\Models\DetailSparepartPenjualan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\KategoriLaciTrait;
use App\Models\PemasukkanLain;
use Illuminate\Support\Facades\DB;


class SalesApiController extends Controller
{
    use KategoriLaciTrait;
    public function search(Request $request)
    {
        try {
            $request->validate(['search' => 'required|string|max:255']);
            $keywords = array_filter(explode(' ', strtolower(trim($request->input('search')))));

            $query = DB::table('spareparts')
                ->where('kode_owner', '=', $this->getThisUser()->id_upline);

            // Gunakan subquery untuk each keyword
            foreach ($keywords as $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                });
            }

            $data = $query->select([
                'id',
                'kode_sparepart',
                'nama_sparepart',
                'harga_beli',
                'harga_ecer',
                'stok_sparepart',
                'created_at',
                'updated_at'
            ])->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'total_items' => $data->count(),
                'data' => $data
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Get sales history
    public function getSalesHistory()
    {
        // Mendapatkan tanggal awal dan akhir bulan saat ini
        $startOfMonth = now()->startOfMonth()->toDateString();
        $endOfMonth = now()->endOfMonth()->toDateString();

        $sales = Penjualan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_penjualan', '!=', '0']
        ])
            ->whereBetween('tgl_penjualan', [$startOfMonth, $endOfMonth]) // Filter satu bulan
            ->latest()
            ->with(['detailBarang', 'detailSparepart'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }


    // Create new sale
    public function createSale(Request $request)
    {
        $totalPenjualan = 0;
        $totalBayar = 0;

        // Ambil tipe customer dari request
        $selectedCustomerType = $request->customer_type; // Misalnya: 'ecer', 'glosir', 'jumbo'

        // Buat data penjualan terlebih dahulu
        $sale = Penjualan::create([
            'kode_penjualan' => 'TRX' . date('Ymd') . auth()->user()->id . (Penjualan::count() + 1),
            'tgl_penjualan' => date('Y-m-d'),
            'kode_owner' => $this->getThisUser()->id_upline,
            'nama_customer' => $request->nama_customer ?? '-',
            'catatan_customer' => $request->catatan_customer ?? '',
            'total_penjualan' => 0,
            'total_bayar' => 0,
            'user_input' => auth()->user()->id,
            'status_penjualan' => '1',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        foreach ($request->items as $item) {
            $qtySparepart = $item['qty'];
            $sparepartId = $item['sparepart_id'];

            // Ambil data sparepart dari database
            $sparepart = Sparepart::findOrFail($sparepartId);

            // Sesuaikan harga jual berdasarkan tipe customer yang didapatkan dari request
            $hargaJual = $this->adjustPriceBasedOnCustomerType($sparepart, $selectedCustomerType);

            // Periksa apakah stok mencukupi
            if ($sparepart->stok_sparepart < $qtySparepart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Stok tidak mencukupi untuk sparepart ID: ' . $sparepartId,
                ], 400);
            }

            // Kurangi stok sparepart
            $sparepart->update([
                'stok_sparepart' => $sparepart->stok_sparepart - $qtySparepart,
            ]);

            // Hitung total penjualan dan pembayaran
            $totalPenjualan += $hargaJual * $qtySparepart;
            $totalBayar += $hargaJual * $qtySparepart;

            // Catat detail penjualan
            DetailSparepartPenjualan::create([
                'kode_penjualan' => $sale->id,
                'kode_sparepart' => $sparepartId,
                'detail_harga_modal' => $sparepart->harga_beli,
                'detail_harga_jual' => $hargaJual,
                'qty_sparepart' => $qtySparepart,
                'user_input' => auth()->user()->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Update total penjualan dan pembayaran setelah selesai
        $sale->update([
            'total_penjualan' => $totalPenjualan,
            'total_bayar' => $totalBayar,
        ]);
        $this->recordLaciHistory(
            $request->kategori_laci_id,
            $totalBayar, // Uang masuk
            null, // Tidak ada uang keluar
            'Penjualan: ' . $sale->kode_penjualan . '- customer: ' . ($request->nama_customer ?? '-')
        );

        return response()->json([
            'status' => 'success',
            'data' => $sale,
        ]);
    }

    private function adjustPriceBasedOnCustomerType($sparepart, $selectedCustomerType)
    {
        $finalPrice = $sparepart->harga_ecer;

        if ($selectedCustomerType == 'ecer') {
            if ($finalPrice < 15000) {
                $finalPrice += $finalPrice * 0.1;
            } elseif ($finalPrice >= 15000 && $finalPrice <= 200000) {
                $finalPrice += 10000;
            } else {
                $finalPrice += 20000;
            }
        } elseif ($selectedCustomerType == 'glosir') {
            if ($finalPrice >= 5000 && $finalPrice < 15000) {
                $finalPrice -= 1000;
            } elseif ($finalPrice >= 50000 && $finalPrice < 200000) {
                $finalPrice -= 5000;
            }
        } elseif ($selectedCustomerType == 'jumbo') {
            if ($finalPrice >= 5000 && $finalPrice < 15000) {
                $finalPrice -= 2000;
            } elseif ($finalPrice >= 50000 && $finalPrice < 200000) {
                $finalPrice -= 10000;
            }
        }

        return $finalPrice;
    }
    public function createPemasukkanLainApi(Request $request)
    {
        // Validasi input request
        $request->validate([
            'jumlah_pemasukan' => ['required', 'numeric'],
        ]);
        try {
            // Buat record pemasukan baru
            $create = PemasukkanLain::create([
                'tgl_pemasukkan' => date('Y-m-d'),
                'judul_pemasukan' => $request->judul_pemasukan,
                'catatan_pemasukkan' => $request->catatan_pemasukan,
                'jumlah_pemasukkan' => $request->jumlah_pemasukan,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            // Jika pemasukan berhasil dibuat, catat histori laci
            if ($create) {
                $kategoriId = $request->id_kategorilaci;
                $uangMasuk = $request->input('jumlah_pemasukan');
                $keterangan = $request->input('judul_pemasukan') . "-" . $request->input('catatan_pemasukan');

                $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);

                return response()->json([
                    'success' => true,
                    'message' => 'Pemasukkan berhasil ditambahkan',
                    'data' => $create,
                ], 201);
            }

            // Jika gagal, kirim response gagal
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pemasukkan, ada kendala teknis',
            ], 500);
        } catch (\Exception $e) {
            // Handle exception dan kirim response error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan pemasukkan',
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ], 500);
        }
    }

    // Get sale detail
    public function getSaleDetail($id)
    {
        $sale = Penjualan::with(['detailBarang', 'detailSparepart'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

    // Update sale
    public function updateSale(Request $request, $id)
    {
        $sale = Penjualan::findOrFail($id);

        $updateData = [
            'tgl_penjualan' => $request->tgl_penjualan,
            'nama_customer' => $request->nama_customer ?? '-',
            'catatan_customer' => $request->catatan_customer ?? '-',
            'total_penjualan' => $request->total_penjualan,
            'total_bayar' => $request->total_bayar,
            'status_penjualan' => $request->status_penjualan,
            'updated_at' => Carbon::now(),
        ];

        if ($request->status_penjualan == '1' && $request->id_kategorilaci) {
            $this->recordLaciHistory(
                $request->id_kategorilaci,
                $request->total_penjualan,
                null,
                $request->nama_customer . "-" . $request->catatan_customer
            );
        }

        $sale->update($updateData);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

    // Delete sale
    public function deleteSale($id)
    {
        $sale = Penjualan::findOrFail($id);
        $sale->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sale deleted successfully'
        ]);
    }
}
