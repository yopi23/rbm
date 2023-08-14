<?php

namespace App\Http\Controllers;

use App\Models\DetailBarangPenjualan;
use App\Models\DetailBarangPesanan;
use App\Models\DetailPartLuarService;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\DetailSparepartPesanan;
use App\Models\PemasukkanLain;
use App\Models\Penarikan;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use App\Models\Pesanan;
use App\Models\Sevices;
use App\Models\Sparepart;
use App\Models\SparepartRusak;
use App\Models\User;
use Illuminate\Http\Request;

use Barryvdh\DomPDF\Facade;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPDFPDF;
use Milon\Barcode\Facades\DNS1DFacade;

class PDFController extends Controller
{
    //
    public function opname_sparepart()
    {
        $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $data_sparepart_rusak = SparepartRusak::join('spareparts', 'sparepart_rusaks.kode_barang', '=', 'spareparts.id')->where('sparepart_rusaks.kode_owner', '=', $this->getThisUser()->id_upline)->get(['sparepart_rusaks.id as id_rusak', 'sparepart_rusaks.*', 'spareparts.*']);
        $pdf = Pdf::loadView('export.pdf.opname', compact(['sparepart', 'data_sparepart_rusak']));
        return $pdf->stream();
    }
    public function nota_service(Request $request, $id)
    {
        $data = Sevices::findOrFail($id);
        $qr = DNS1DFacade::getBarcodeHTML($data->kode_service, "C39", .65, 60);
        $customPaper = array(0, 0, 567.00, 180.80);
        $pdf = PDF::loadView('export.pdf.nota_service', compact(['data', 'qr']))->setPaper($customPaper, 'landscape');
        return $pdf->stream();
    }
    public function nota_tempel(Request $request, $id)
    {
        $data = Sevices::findOrFail($id);
        $qr = DNS1DFacade::getBarcodeHTML($data->kode_service, "C39", .65, 60);
        $customPaper = array(0, 0, 567.00, 180.80);
        $pdf = PDF::loadView('export.pdf.nota_tempel', compact(['data', 'qr']))->setPaper($customPaper, 'landscape');
        return $pdf->stream();
    }
    public function nota_tempel_selesai(Request $request, $id)
    {
        $data = Sevices::join('users', 'sevices.id_teknisi', '=', 'users.id')
            ->where('sevices.id', $id)
            ->select('sevices.id as sevice_id', 'sevices.updated_at as tgl_selesai', 'sevices.*', 'users.*')
            ->firstOrFail();
        $qr = DNS1DFacade::getBarcodeHTML($data->kode_service, "C39", .65, 60);
        $customPaper = array(0, 0, 567.00, 180.80);
        $pdf = PDF::loadView('export.pdf.nota_selesai', compact(['data', 'qr']))->setPaper($customPaper, 'landscape');
        return $pdf->stream();
    }
    public function tag_name(Request $request, $id)
    {
        $data = Sparepart::where('spareparts.id', $id)
            ->select('spareparts.id as spareparts_id', 'spareparts.*')
            ->firstOrFail();
        $qr = DNS1DFacade::getBarcodeHTML($data->kode_sparepart, "C128", .65, 60);
        $customPaper = array(0, 0, 567.00, 180.80);
        $pdf = PDF::loadView('export.pdf.Barcode_barang', compact(['data', 'qr']))->setPaper($customPaper, 'landscape');
        return $pdf->stream();
    }
    public function print_laporan(Request $request)
    {
        if (isset($request->tgl_awal) && isset($request->tgl_akhir)) {
            //Service
            $service = Sevices::where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['sevices.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['sevices.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $part_toko_service = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['detail_part_services.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['detail_part_services.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.created_at as tgl_keluar', 'detail_part_services.*', 'spareparts.*', 'sevices.*']);
            $part_luar_toko_service = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')->where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['detail_part_luar_services.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['detail_part_luar_services.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_luar_services.id as id_part', 'detail_part_luar_services.created_at as tgl_keluar', 'detail_part_luar_services.*', 'sevices.*']);
            $all_part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['spareparts.kode_owner', '=', $this->getThisUser()->id_upline]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
            $all_part_luar_toko_service = DetailPartLuarService::latest()->get();
            //Penjualan
            $penjualan_sparepart = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')
                ->where([['penjualans.kode_owner', '=', $this->getThisUser()->id_upline], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_sparepart_penjualans.created_at as tgl_keluar', 'detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*', 'penjualans.*']);
            $penjualan_barang = DetailBarangPenjualan::join('penjualans', 'detail_barang_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')
                ->where([['penjualans.kode_owner', '=', $this->getThisUser()->id_upline], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_barang_penjualans.created_at as tgl_keluar', 'detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*', 'penjualans.*']);
            //Pesanan
            $sparepart_pesanan = DetailSparepartPesanan::join('pesanans', 'detail_sparepart_pesanans.kode_pesanan', '=', 'pesanans.id')
                ->join('spareparts', 'detail_sparepart_pesanans.kode_sparepart', '=', 'spareparts.id')
                ->where([['pesanans.kode_owner', '=', $this->getThisUser()->id_upline], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_sparepart_pesanans.created_at as tgl_keluar', 'detail_sparepart_pesanans.id as id_sparepart_pesanan', 'detail_sparepart_pesanans.*', 'spareparts.*', 'pesanans.*']);
            $barang_pesanan = DetailBarangPesanan::join('pesanans', 'detail_barang_pesanans.kode_pesanan', '=', 'pesanans.id')
                ->join('handphones', 'detail_barang_pesanans.kode_barang', '=', 'handphones.id')
                ->where([['pesanans.kode_owner', '=', $this->getThisUser()->id_upline], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_barang_pesanans.created_at as tgl_keluar', 'detail_barang_pesanans.id as id_barang_pesanan', 'detail_barang_pesanans.*', 'handphones.*', 'pesanans.*']);
            //Pemasukkan Lain
            $pemasukkan_lain = PemasukkanLain::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pemasukkan_lains.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pemasukkan_lains.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            //Pengeluaran
            //Penarikan
            $penarikan = Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')->where([['penarikans.status_penarikan', '=', '1'], ['penarikans.kode_owner', '=', $this->getThisUser()->id_upline], ['penarikans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penarikans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['penarikans.id as id_penarikan', 'penarikans.*', 'user_details.*']);
            $pengeluaran_toko = PengeluaranToko::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pengeluaran_tokos.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_tokos.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $pengeluaran_opx = PengeluaranOperasional::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pengeluaran_operasionals.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_operasionals.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $pdf = PDF::loadView('export.pdf.laporan', compact(['penarikan', 'barang_pesanan', 'sparepart_pesanan', 'penjualan_barang', 'penjualan_sparepart', 'pengeluaran_opx', 'pengeluaran_toko', 'pemasukkan_lain', 'request', 'service', 'part_toko_service', 'part_luar_toko_service', 'all_part_toko_service', 'all_part_luar_toko_service']));
            return $pdf->stream();
        }
        return redirect()->back();
    }
    public function print_laporan_owner(Request $request)
    {
        $owner = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where([['user_details.jabatan', '=', '1'], ['users.id', '=', $request->kode_owner]])->get(['users.*', 'user_details.*', 'users.id as id_user'])->first();
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

            $pdf = PDF::loadView('export.pdf.laporan_owner', compact(['barang_pesanan', 'owner', 'sparepart_pesanan', 'penjualan_barang', 'penjualan_sparepart', 'pengeluaran_opx', 'pengeluaran_toko', 'pemasukkan_lain', 'pesanan', 'penjualan', 'request', 'service', 'part_toko_service', 'part_luar_toko_service', 'all_part_toko_service', 'all_part_luar_toko_service']));
            return $pdf->stream();
        }
        return redirect()->back();
    }
}
