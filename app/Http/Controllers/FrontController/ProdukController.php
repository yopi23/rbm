<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\Handphone;
use App\Models\UserDetail;
use Illuminate\Http\Request;

class ProdukController extends Controller
{
    //
    public function index(Request $request){
        $data = null;
        $ismember = false;
        if(isset($request->q) != null){
            $data = Handphone::join('kategori_handphones','handphones.kode_kategori','=','kategori_handphones.id')->where([['nama_barang','LIKE','%'.$request->q.'%']])->get(['handphones.id as id_produk','handphones.*','kategori_handphones.*']);
        }
        if(isset($request->ref) != null){
            $cek = UserDetail::where([['kode_invite','=',$request->ref]])->get()->first();
            if($cek){
                $ismember = true;
                $data = Handphone::join('kategori_handphones','handphones.kode_kategori','=','kategori_handphones.id')->where([['handphones.kode_owner','=',$cek->id_upline],['handphones.nama_barang','LIKE','%'.$request->q.'%']])->get(['handphones.id as id_produk','handphones.*','kategori_handphones.*']);
            }
        }
        return view('front.produk',compact(['data','request','ismember']));
    }
}
