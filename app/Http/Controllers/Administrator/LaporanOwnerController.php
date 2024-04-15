<?php

namespace App\Http\Controllers\Administrator;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailBarangPesanan;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\DetailSparepartPesanan;
use App\Models\PemasukkanLain;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\Pesanan;
use App\Models\Sevices;
use Illuminate\Http\Request;

class LaporanOwnerController extends Controller
{
    //
    public function index(Request $request)
    {
        $page = "Laporan Owner";
        $owner = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '=', '1']])->get(['users.*', 'user_details.*', 'users.id as id_user']);
        $content = view('admin.page.laporan_owner', compact(['request', 'owner']));
        if (isset($request->tgl_awal) && isset($request->tgl_akhir) && isset($request->kode_owner)) {
            //Service
            $service = Sevices::where([['sevices.kode_owner', '=', $request->kode_owner], ['sevices.status_services', '=', 'Diambil'], ['sevices.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['sevices.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $part_toko_service = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['sevices.kode_owner', '=', $request->kode_owner], ['sevices.status_services', '=', 'Diambil'], ['detail_part_services.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['detail_part_services.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.created_at as tgl_keluar', 'detail_part_services.*', 'spareparts.*', 'sevices.*']);
            $part_luar_toko_service = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')->where([['sevices.kode_owner', '=', $request->kode_owner], ['sevices.status_services', '=', 'Diambil'], ['detail_part_luar_services.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['detail_part_luar_services.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_luar_services.id as id_part', 'detail_part_luar_services.created_at as tgl_keluar', 'detail_part_luar_services.*', 'sevices.*']);
            $all_part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['spareparts.kode_owner', '=', $request->kode_owner]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
            $all_part_luar_toko_service = DetailPartLuarService::latest()->get();
            //Penjualan
            $penjualan = Penjualan::where([['kode_owner', '=', $request->kode_owner], ['status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $penjualan_sparepart = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')
                ->where([['penjualans.kode_owner', '=', $request->kode_owner], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_sparepart_penjualans.created_at as tgl_keluar', 'detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*', 'penjualans.*']);
            $penjualan_barang = DetailBarangPenjualan::join('penjualans', 'detail_barang_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')
                ->where([['penjualans.kode_owner', '=', $request->kode_owner], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_barang_penjualans.created_at as tgl_keluar', 'detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*', 'penjualans.*']);
            //Pesanan
            $pesanan = Pesanan::where([['kode_owner', '=', $request->kode_owner], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $sparepart_pesanan = DetailSparepartPesanan::join('pesanans', 'detail_sparepart_pesanans.kode_pesanan', '=', 'pesanans.id')
                ->join('spareparts', 'detail_sparepart_pesanans.kode_sparepart', '=', 'spareparts.id')
                ->where([['pesanans.kode_owner', '=', $request->kode_owner], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_sparepart_pesanans.created_at as tgl_keluar', 'detail_sparepart_pesanans.id as id_sparepart_pesanan', 'detail_sparepart_pesanans.*', 'spareparts.*', 'pesanans.*']);
            $barang_pesanan = DetailBarangPesanan::join('pesanans', 'detail_barang_pesanans.kode_pesanan', '=', 'pesanans.id')
                ->join('handphones', 'detail_barang_pesanans.kode_barang', '=', 'handphones.id')
                ->where([['pesanans.kode_owner', '=', $request->kode_owner], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_barang_pesanans.created_at as tgl_keluar', 'detail_barang_pesanans.id as id_barang_pesanan', 'detail_barang_pesanans.*', 'handphones.*', 'pesanans.*']);
            //Pemasukkan Lain
            $pemasukkan_lain = PemasukkanLain::where([['kode_owner', '=', $request->kode_owner], ['pemasukkan_lains.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pemasukkan_lains.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            //Pengeluaran
            $pengeluaran_toko = PengeluaranToko::where([['kode_owner', '=', $request->kode_owner], ['pengeluaran_tokos.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_tokos.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $pengeluaran_opx = PengeluaranOperasional::where([['kode_owner', '=', $request->kode_owner], ['pengeluaran_operasionals.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_operasionals.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();

            $content = view('admin.page.laporan_owner', compact(['barang_pesanan', 'owner', 'sparepart_pesanan', 'penjualan_barang', 'penjualan_sparepart', 'pengeluaran_opx', 'pengeluaran_toko', 'pemasukkan_lain', 'pesanan', 'penjualan', 'request', 'service', 'part_toko_service', 'part_luar_toko_service', 'all_part_toko_service', 'all_part_luar_toko_service']));
        }
        return view('admin.layout.blank_page', compact(['page', 'content']));
    }
}
