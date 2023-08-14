<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailBarangPesanan;
use App\Models\DetailSparepartPesanan;
use App\Models\Pesanan;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    //
    public function index(){
        $page = "Pesanan";
        $data = Pesanan::where('kode_owner','=',$this->getThisUser()->id_upline)->latest()->get();
        $content = view('admin.page.pesanan',compact(['data']));
        return view('admin.layout.blank_page',compact(['page','content']));
    }
    public function edit(Request $request,$id){
        $page = "Detail Pesanan";
        $data = Pesanan::findOrFail($id);
        $sparepart = DetailSparepartPesanan::join('spareparts','detail_sparepart_pesanans.kode_sparepart','=','spareparts.id')->where([['detail_sparepart_pesanans.kode_pesanan','=',$id]])->get(['detail_sparepart_pesanans.id as id_sparepart_pesanan','detail_sparepart_pesanans.*','spareparts.*']);
        $barang = DetailBarangPesanan::join('handphones','detail_barang_pesanans.kode_barang','=','handphones.id')->where([['detail_barang_pesanans.kode_pesanan','=',$id]])->get(['detail_barang_pesanans.id as id_barang_pesanan','detail_barang_pesanans.*','handphones.*']);
        return view('admin.forms.pesanan',compact(['page','data','sparepart','barang']));
    }
    public function update(Request $request,$id){
        $update = Pesanan::findOrFail($id);
        $update->update([
            'status_pesanan' => $request->status_pesanan,
            'total_bayar' => $request->total_bayar,
        ]);
        if($update){
            return redirect()->back()->with([
                'success' => 'Edit Pesanan Berhasil'
            ]);
        }
    }
}
