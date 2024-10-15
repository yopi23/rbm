<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Catatan;
use App\Models\DetailPartServices;
use App\Models\ListOrder;
use App\Models\PemasukkanLain;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Penarikan;
use App\Models\Laci;
use Illuminate\Support\Facades\Http;
use App\Traits\KategoriLaciTrait;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function searchSparepart(Request $request)
    {
        $query = $request->input('search');

        $sparepart = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
            ->when($query, function ($queryBuilder) use ($query) {
                return $queryBuilder->where('nama_sparepart', 'LIKE', "%{$query}%")
                    ->orWhere('kode_sparepart', 'LIKE', "%{$query}%");
            })
            ->latest()
            ->get();

        return response()->json($sparepart); // Mengembalikan hasil pencarian dalam format JSON
    }
}
