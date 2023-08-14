<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\Sparepart;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class SparepartController extends Controller
{
    //
    public function index(Request $request){
        $data = null;
        $ismember = false;
        if(isset($request->q) != null){
            $data = Sparepart::join('kategori_spareparts','spareparts.kode_kategori','=','kategori_spareparts.id')->where([['nama_sparepart','LIKE','%'.$request->q.'%']])->get(['spareparts.id as id_produk','spareparts.*','kategori_spareparts.*']);
        }
        if(isset($request->ref) != null){
            $cek = UserDetail::where([['kode_invite','=',$request->ref]])->get()->first();
            if($cek){
                $ismember = true;
                $data = Sparepart::join('kategori_spareparts','spareparts.kode_kategori','=','kategori_spareparts.id')->where([['spareparts.kode_owner','=',$cek->id_upline],['spareparts.nama_sparepart','LIKE','%'.$request->q.'%']])->get(['spareparts.id as id_produk','spareparts.*','kategori_spareparts.*']);
            }
        }
        return view('front.sparepart',compact(['request','ismember','data']));
    }
}
