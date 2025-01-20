<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;

use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\KategoriLaciTrait;
use Illuminate\Support\Facades\DB;

class SalesApiController extends Controller
{
    use KategoriLaciTrait;
    public function search(Request $request)
    {
        try {
            $searchQuery = $request->search;

            $data = DB::table('spareparts')
                ->where([
                    ['nama_sparepart', 'LIKE', '%' . $searchQuery . '%'],
                    ['kode_owner', '=', $this->getThisUser()->id_upline]
                ])
                ->select([
                    'id',
                    'kode_sparepart',
                    'nama_sparepart',
                    'harga_beli',
                    'harga_ecer',
                    'stok_sparepart',
                    'created_at',
                    'updated_at'
                ])
                ->get();

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
        $sales = Penjualan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_penjualan', '!=', '0']
        ])
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
        $count = Penjualan::where([
            ['user_input', '=', auth()->user()->id],
            ['kode_owner', '=', $this->getThisUser()->id_upline]
        ])->count();

        $kode = 'TRX' . date('Ymd') . auth()->user()->id . $count;

        $sale = Penjualan::create([
            'kode_penjualan' => $kode,
            'kode_owner' => $this->getThisUser()->id_upline,
            'nama_customer' => $request->nama_customer ?? '-',
            'catatan_customer' => $request->catatan_customer ?? '',
            'total_bayar' => '0',
            'total_penjualan' => '0',
            'user_input' => auth()->user()->id,
            'status_penjualan' => '0',
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
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
