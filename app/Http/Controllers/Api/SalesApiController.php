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
                'harga_jual',
                'harga_ecer',
                'harga_pasang',
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
        // Mendapatkan tanggal 7 hari terakhir
        $oneWeekAgo = now()->subDays(7)->toDateString();
        $today = now()->toDateString();

        $sales = Penjualan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_penjualan', '!=', '0']
        ])
            ->whereBetween('tgl_penjualan', [$oneWeekAgo, $today]) // Filter 7 hari terakhir
            ->latest()
            ->with(['detailBarang', 'detailSparepart'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }

    // public function createSale(Request $request)
    // {
    //     $totalPenjualan = 0;
    //     $totalBayar = 0;

    //     // Cek apakah transaksi hanya disimpan sebagai draft (status 2)
    //     $statusPenjualan = ($request->simpan == 'simpan') ? '2' : '1';

    //     // Buat data penjualan terlebih dahulu
    //     $sale = Penjualan::create([
    //         'kode_penjualan' => 'TRX' . date('Ymd') . auth()->user()->id . (Penjualan::count() + 1),
    //         'tgl_penjualan' => date('Y-m-d'),
    //         'kode_owner' => $this->getThisUser()->id_upline,
    //         'nama_customer' => $request->nama_customer ?? '-',
    //         'catatan_customer' => $request->catatan_customer ?? '',
    //         'total_penjualan' => 0,
    //         'total_bayar' => 0,
    //         'user_input' => auth()->user()->id,
    //         'status_penjualan' => $statusPenjualan, // Bisa 1 (langsung bayar) atau 2 (draft)
    //         'created_at' => now(),
    //         'updated_at' => now(),
    //     ]);

    //     foreach ($request->items as $item) {
    //         $qtySparepart = $item['qty'];
    //         $sparepartId = $item['sparepart_id'];

    //         // Ambil data sparepart dari database
    //         $sparepart = Sparepart::findOrFail($sparepartId);

    //         $itemCustomerType = isset($item['customer_type']) &&
    //                in_array($item['customer_type'], ['ecer', 'konter', 'glosir', 'jumbo'])
    //                ? $item['customer_type']
    //                : 'ecer';

    //         // Sesuaikan harga jual berdasarkan tipe customer
    //         $hargaJual = $this->adjustPriceBasedOnCustomerType($sparepart, $itemCustomerType);

    //         // Periksa apakah stok mencukupi
    //         if ($sparepart->stok_sparepart < $qtySparepart) {
    //             return response()->json([
    //                 'status' => 'error',
    //                 'message' => 'Stok tidak mencukupi untuk sparepart ID: ' . $sparepartId,
    //             ], 400);
    //         }

    //         // Kurangi stok sparepart hanya jika status bukan draft
    //         if ($statusPenjualan == '1') {
    //             $sparepart->update([
    //                 'stok_sparepart' => $sparepart->stok_sparepart - $qtySparepart,
    //             ]);
    //         }

    //         // Hitung total penjualan
    //         $totalPenjualan += $hargaJual * $qtySparepart;
    //         $totalBayar += $hargaJual * $qtySparepart;

    //         // Catat detail penjualan
    //         DetailSparepartPenjualan::create([
    //             'kode_penjualan' => $sale->id,
    //             'kode_sparepart' => $sparepartId,
    //             'detail_harga_modal' => $sparepart->harga_beli,
    //             'detail_harga_jual' => $hargaJual,
    //             'qty_sparepart' => $qtySparepart,
    //             'user_input' => auth()->user()->id,
    //             'created_at' => now(),
    //             'updated_at' => now(),
    //         ]);
    //     }

    //     // Update total penjualan
    //     $sale->update([
    //         'total_penjualan' => $totalPenjualan,
    //         'total_bayar' => ($statusPenjualan == '1') ? $totalBayar : 0, // Hanya update jika status bukan draft
    //     ]);

    //     // Jika status bukan draft (status 1), maka tambahkan ke laci
    //     if ($statusPenjualan == '1') {
    //         $this->recordLaciHistory(
    //             $request->kategori_laci_id,
    //             $totalBayar, // Uang masuk
    //             null, // Tidak ada uang keluar
    //             'Penjualan: ' . $sale->kode_penjualan . '- customer: ' . ($request->nama_customer ?? '-')
    //         );
    //     }

    //     return response()->json([
    //         'status' => 'success',
    //         'message' => ($statusPenjualan == '2') ? 'Penjualan disimpan sebagai draft.' : 'Penjualan berhasil disimpan.',
    //         'data' => $sale,
    //     ]);
    // }

    // Perbaikan pada method createSale di SalesApiController.php
    public function createSale(Request $request)
    {
        $totalPenjualan = 0;
        $totalBayar = 0;

        // Cek apakah transaksi hanya disimpan sebagai draft (status 2)
        $statusPenjualan = ($request->simpan == 'simpan') ? '2' : '1';

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
            'status_penjualan' => $statusPenjualan, // Bisa 1 (langsung bayar) atau 2 (draft)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Array untuk melacak error stok
        $stockErrors = [];

        foreach ($request->items as $item) {
            $qtySparepart = $item['qty'];
            $sparepartId = $item['sparepart_id'];

            try {
                // Ambil data sparepart dari database
                $sparepart = Sparepart::findOrFail($sparepartId);

                // Sesuaikan harga jual berdasarkan tipe customer
                $hargaJual = $this->adjustPriceBasedOnCustomerType($sparepart, $item['customer_type'] ?? 'ecer');

                // PERBAIKAN: Periksa stok secara individu untuk masing-masing item
                if ($sparepart->stok_sparepart < $qtySparepart) {
                    $stockErrors[] = [
                        'id' => $sparepartId,
                        'name' => $sparepart->nama_sparepart,
                        'requested' => $qtySparepart,
                        'available' => $sparepart->stok_sparepart
                    ];
                    continue; // Lewati item ini tapi lanjutkan dengan yang lain
                }

                 // Kurangi stok berdasarkan qty masing-masing item
                // $sparepart->update([
                //     'stok_sparepart' => $sparepart->stok_sparepart - $qtySparepart,
                // ]);

                // \Log::info('Sparepart after update:', [
                //     'id' => $sparepartId,
                //     'name' => $sparepart->nama_sparepart,
                //     'new_stock' => $sparepart->stok_sparepart,
                //     'qty_stock' => $qtySparepart
                // ]);

                // Hitung total penjualan
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
            } catch (\Exception $e) {
                $stockErrors[] = [
                    'id' => $sparepartId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Update total penjualan
        $sale->update([
            'total_penjualan' => $totalPenjualan,
            'total_bayar' => ($statusPenjualan == '1') ? $totalBayar : 0, // Hanya update jika bukan draft
        ]);

        // Jika status bukan draft (status 1), maka tambahkan ke laci
        if ($statusPenjualan == '1') {
            $this->recordLaciHistory(
                $request->kategori_laci_id,
                $totalBayar, // Uang masuk
                null, // Tidak ada uang keluar
                'Penjualan: ' . $sale->kode_penjualan . '- customer: ' . ($request->nama_customer ?? '-')
            );
        }

        return response()->json([
            'status' => $stockErrors ? 'partial_success' : 'success',
            'message' => ($statusPenjualan == '2') ? 'Penjualan disimpan sebagai draft.' : 'Penjualan berhasil disimpan.',
            'data' => $sale,
            'stock_errors' => $stockErrors
        ]);

    }
    public function updateSaleStatus(Request $request)
    {
        $request->validate([
            'id_penjualan' => 'required|exists:penjualans,id',
        ]);

        // Cari data penjualan berdasarkan kode_penjualan
        $sale = Penjualan::where('id', $request->id_penjualan)->first();

        if (!$sale) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penjualan tidak ditemukan',
            ], 404);
        }

        // Cek apakah status sudah lunas
        if ($sale->status_penjualan == '1') {
            return response()->json([
                'status' => 'error',
                'message' => 'Penjualan ini sudah lunas',
            ], 400);
        }

        // Update status menjadi lunas (1)
        $sale->update([
            'status_penjualan' => '1',
            'total_bayar' => $sale->total_penjualan, // Update total bayar
            'updated_at' => now(),
        ]);

        // Catat ke laci jika dibutuhkan
        $this->recordLaciHistory(
            $request->kategori_laci_id,
            $sale->total_penjualan, // Uang masuk
            null,
            'Pelunasan: ' . $sale->kode_penjualan . ' - Customer: ' . ($sale->nama_customer ?? '-')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status penjualan berhasil diperbarui menjadi lunas.',
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

    // Get sale detail
    public function getSaleDetail($id)
    {
        $sale = Penjualan::with(['detailBarang', 'detailSparepart.sparepart:id,nama_sparepart'])
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

    // Tambahkan method baru ini di SalesApiController.php
    public function cancelSale(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            // Temukan penjualan
            $sale = Penjualan::findOrFail($id);

            // Hanya lanjutkan jika penjualan belum diproses/diselesaikan
            if ($sale->status_penjualan == '1') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat membatalkan penjualan yang sudah diproses'
                ], 400);
            }

            // Dapatkan semua detail penjualan
            $saleDetails = DetailSparepartPenjualan::where('kode_penjualan', $sale->id)->get();

            // Kembalikan stok untuk setiap item
            foreach ($saleDetails as $detail) {
                $sparepart = Sparepart::find($detail->kode_sparepart);
                if ($sparepart) {
                    $sparepart->update([
                        'stok_sparepart' => $sparepart->stok_sparepart + $detail->qty_sparepart
                    ]);
                }
            }

            // Hapus detail penjualan
            DetailSparepartPenjualan::where('kode_penjualan', $sale->id)->delete();

            // Hapus penjualan
            $sale->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'message' => 'Penjualan berhasil dibatalkan dan stok dikembalikan'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan penjualan: ' . $e->getMessage()
            ], 500);
        }
    }
}
