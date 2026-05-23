<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\KategoriSparepart;
use App\Models\Sparepart;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class SparepartController extends Controller
{
    //
    public function index(Request $request){
        $query = Sparepart::join('kategori_spareparts','spareparts.kode_kategori','=','kategori_spareparts.id')
            ->where(function($q) {
                $q->where('kategori_spareparts.is_active', 1)->orWhereNull('kategori_spareparts.is_active');
            })
            ->where('spareparts.is_visible_on_web', 1);

        if ($request->filled('q')) {
            $query->where('nama_sparepart', 'LIKE', '%' . $request->q . '%');
        }

        if ($request->filled('kategori')) {
            $query->where('spareparts.kode_kategori', $request->kategori);
        }

        $ismember = false;
        if ($request->filled('ref')) {
            $cek = UserDetail::where('kode_invite', $request->ref)->first();
            if ($cek) {
                $ismember = true;
                $query->where('spareparts.kode_owner', $cek->id_upline);
            }
        }

        $data = $query->select([
            'spareparts.id as id_produk',
            'spareparts.kode_sparepart',
            'spareparts.nama_sparepart',
            'spareparts.desc_sparepart',
            'spareparts.foto_sparepart',
            'spareparts.stok_sparepart',
            'spareparts.harga_beli',
            'spareparts.harga_jual',
            'spareparts.harga_ecer',
            'spareparts.harga_pasang',
            'spareparts.is_active',
            'kategori_spareparts.nama_kategori',
        ])->get();

        // Get all active categories for filter tabs
        $categories = KategoriSparepart::where(function($q) {
            $q->where('is_active', 1)->orWhereNull('is_active');
        })->orderBy('nama_kategori')->get();
        
        return view('front.sparepart',compact(['request','ismember','data','categories']));
    }

    public function show(Request $request, $id)
    {
        $sparepart = Sparepart::with('kategori')
            ->where('spareparts.id', $id)
            ->where('spareparts.is_visible_on_web', 1)
            ->first();

        if (!$sparepart) {
            abort(404);
        }

        // Check membership for price visibility
        $ismember = false;
        $ref = $request->query('ref');
        if ($ref) {
            $cek = UserDetail::where('kode_invite', $ref)->first();
            if ($cek) {
                $ismember = true;
            }
        }

        return view('front.sparepart_detail', compact('sparepart', 'ismember', 'ref'));
    }
}
