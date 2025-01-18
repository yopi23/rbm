<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
use App\Models\Hutang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LaporanController extends Controller
{
    //
    public function view_laporan(Request $request)
    {
        $page = "Laporan";
        $content = view('admin.page.laporan');

        $totalPendapatanService = 0;
        $DpService = 0;
        $totalPenjualan = 0;
        $totalModalJual = 0;
        $total_part_service = 0;
        $totalPemasukkanLain = 0; // Variabel baru untuk Total Pendapatan Pemasukkan Lain
        // Perhitungan totalLaba tanpa memasukkan totalPemasukkanLain
        $totalLaba = ($totalPenjualan - $totalModalJual) + ($totalPendapatanService - $total_part_service);

        if (isset($request->tgl_awal) && isset($request->tgl_akhir)) {

            //Service
            $serviceDp = Sevices::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            // $service = Sevices::where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['sevices.updated_at', '>=', $request->tgl_awal . ' 00:00:00'], ['sevices.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();

            $service = DB::table('sevices')
                ->join('profit_presentases', 'sevices.id', '=', 'profit_presentases.kode_service') // Menghubungkan id dan kode_service
                ->join('users', 'sevices.id_teknisi', '=', 'users.id') // Menghubungkan id dan is
                ->where([
                    ['sevices.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['sevices.status_services', '=', 'Diambil'],
                    ['sevices.updated_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['sevices.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']
                ])
                ->select('sevices.*', 'profit_presentases.profit', 'users.name') // Memilih kolom yang dibutuhkan
                ->latest()
                ->get();

            $part_toko_service = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['detail_part_services.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['detail_part_services.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.created_at as tgl_keluar', 'detail_part_services.*', 'spareparts.*', 'sevices.*', 'sevices.updated_at']);
            $part_toko = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['sevices.updated_at', '>=', $request->tgl_awal . ' 00:00:00'], ['sevices.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.created_at as tgl_keluar', 'detail_part_services.*', 'spareparts.*', 'sevices.*', 'sevices.updated_at']);
            $part_luar_toko_service = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')
                ->where([['sevices.kode_owner', '=', $this->getThisUser()->id_upline], ['sevices.status_services', '=', 'Diambil'], ['sevices.updated_at', '>=', $request->tgl_awal . ' 00:00:00'], ['sevices.updated_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_part_luar_services.id as id_part', 'detail_part_luar_services.updated_at as tgl_keluar', 'detail_part_luar_services.*', 'sevices.*']);

            $all_part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['spareparts.kode_owner', '=', $this->getThisUser()->id_upline]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
            $all_part_luar_toko_service = DetailPartLuarService::latest()->get();


            //Penjualan
            // $penjualan = Penjualan::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '=', '1'],  ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $penjualan = Penjualan::join('detail_sparepart_penjualans', 'penjualans.id', '=', 'detail_sparepart_penjualans.kode_penjualan')
                ->where([
                    ['penjualans.kode_owner', '=', $this->getThisUser()->id_upline],
                    ['penjualans.status_penjualan', '=', '1'],
                    ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'],
                    ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59'],
                ])
                ->where('detail_sparepart_penjualans.status_rf', '=', 0)  // Kondisi untuk memilih detail dengan status_rf = 0
                ->select('penjualans.*', 'detail_sparepart_penjualans.status_rf') // Pilih kolom yang diinginkan
                ->latest()
                ->get();

            $penjualan_sparepart = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')
                ->where([['penjualans.kode_owner', '=', $this->getThisUser()->id_upline], ['penjualans.status_penjualan', '=', '1'], ['detail_sparepart_penjualans.status_rf', '=', '0'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_sparepart_penjualans.created_at as tgl_keluar', 'detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*', 'penjualans.*']);
            $penjualan_barang = DetailBarangPenjualan::join('penjualans', 'detail_barang_penjualans.kode_penjualan', '=', 'penjualans.id')
                ->join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')
                ->where([['penjualans.kode_owner', '=', $this->getThisUser()->id_upline], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])
                ->get(['detail_barang_penjualans.created_at as tgl_keluar', 'detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*', 'penjualans.*']);

            //Pesanan
            $pesanan = Pesanan::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pesanans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
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
            $pengeluaran_toko = PengeluaranToko::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pengeluaran_tokos.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_tokos.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            $pengeluaran_opx = PengeluaranOperasional::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['pengeluaran_operasionals.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['pengeluaran_operasionals.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->latest()->get();
            //Penarikan
            $penarikan = Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')->where([['penarikans.status_penarikan', '=', '1'], ['penarikans.kode_owner', '=', $this->getThisUser()->id_upline], ['penarikans.created_at', '>=', $request->tgl_awal . ' 00:00:00'], ['penarikans.created_at', '<=', $request->tgl_akhir . ' 23:59:59']])->get(['penarikans.id as id_penarikan', 'penarikans.*', 'user_details.*']);

            //service di ambil
            foreach ($serviceDp as $item) {

                $tglService = date('Y-m-d', strtotime($item->updated_at));

                if ($tglService >= $request->tgl_awal && $tglService <= $request->tgl_akhir) {
                    if ($item->status_services == 'Diambil') {
                        // Kurangi DP dari total_biaya sebelum menambahkan ke totalPendapatanService
                        $pendapatanServiceSetelahDP = $item->total_biaya - $item->dp;
                        $totalPendapatanService += $pendapatanServiceSetelahDP;
                    }
                }
            }
            foreach ($serviceDp as $item) {

                $tglService = date('Y-m-d', strtotime($item->created_at));

                if ($tglService >= $request->tgl_awal && $tglService <= $request->tgl_akhir) {

                    $DpService += $item->dp;
                }
            }


            //penjualan
            // Penjualan Sparepart
            foreach ($penjualan_sparepart as $item) {
                $tglKeluar = date('Y-m-d', strtotime($item->tgl_penjualan));
                if ($tglKeluar >= $request->tgl_awal && $tglKeluar <= $request->tgl_akhir) {
                    $totalPenjualan += $item->detail_harga_jual * $item->qty_sparepart;
                    $totalModalJual += $item->detail_harga_modal * $item->qty_sparepart;
                }
            }

            // Penjualan Barang
            foreach ($penjualan_barang as $item) {
                $tglKeluar = date('Y-m-d', strtotime($item->tgl_penjualan));
                if ($tglKeluar >= $request->tgl_awal && $tglKeluar <= $request->tgl_akhir) {
                    $totalPenjualan += $item->detail_harga_jual * $item->qty_barang;
                    $totalModalJual += $item->detail_harga_modal * $item->qty_barang;
                }
            }
            // Penjualan Pesanan Sparepart
            foreach ($sparepart_pesanan as $item) {
                $tglKeluar = date('Y-m-d', strtotime($item->tgl_penjualan));
                if ($tglKeluar >= $request->tgl_awal && $tglKeluar <= $request->tgl_akhir) {
                    $totalPenjualan += $item->detail_harga_pesan * $item->qty_sparepart;
                    $totalModalJual += $item->detail_modal_pesan * $item->qty_sparepart;
                }
            }
            // Penjualan Pesanan Barang
            foreach ($barang_pesanan as $item) {
                $tglKeluar = date('Y-m-d', strtotime($item->tgl_penjualan));
                if ($tglKeluar >= $request->tgl_awal && $tglKeluar <= $request->tgl_akhir) {
                    $totalPenjualan += $item->detail_harga_pesan * $item->qty_barang;
                    $totalModalJual += $item->detail_modal_pesan * $item->qty_barang;
                }
            }
            // Total Pendapatan Pemasukkan Lain
            foreach ($pemasukkan_lain as $item) {
                $tglPemasukkan = date('Y-m-d', strtotime($item->created_at));
                if ($tglPemasukkan >= $request->tgl_awal && $tglPemasukkan <= $request->tgl_akhir) {
                    $totalPemasukkanLain += $item->jumlah_pemasukkan;
                }
            }
            // Total part_toko_service
            foreach ($part_toko as $item) {

                $tglService = date('Y-m-d', strtotime($item->updated_at));;

                if ($tglService >= $request->tgl_awal && $tglService <= $request->tgl_akhir) {
                    // if ($item->status_services == 'Diambil') {
                    $total_part_service += $item->detail_harga_part_service;
                    //         } else {
                    // $totalPendapatan += $item->dp;
                    // }
                }
            }
            // Total part_luar_toko_service
            foreach ($part_luar_toko_service as $item) {
                $tglPemasukkan = date('Y-m-d', strtotime($item->updated_at));
                if ($tglPemasukkan >= $request->tgl_awal && $tglPemasukkan <= $request->tgl_akhir) {
                    $total_part_service += $item->harga_part * $item->qty_part;
                }
            }

            // Perhitungan totalLaba tetap tanpa memasukkan totalPemasukkanLain
            $totalLaba = ($totalPenjualan - $totalModalJual) + ($totalPendapatanService - $total_part_service);

            $content = view('admin.page.laporan', compact([
                'penarikan',
                'barang_pesanan',
                'sparepart_pesanan',
                'penjualan_barang',
                'penjualan_sparepart',
                'pengeluaran_opx',
                'pengeluaran_toko',
                'pemasukkan_lain',
                'pesanan',
                'penjualan',
                'request',
                'service',
                'part_toko_service',
                'part_luar_toko_service',
                'all_part_toko_service',
                'all_part_luar_toko_service',
                'totalPendapatanService',
                'DpService',
                'totalPenjualan',
                'total_part_service',
                'totalModalJual',
                'totalLaba',
                'totalPemasukkanLain'
            ]))->render();
        } else {
            $hutang = Hutang::join('suppliers', 'hutang.kode_supplier', '=', 'suppliers.id')
                ->where('hutang.kode_owner', '=', $this->getThisUser()->id_upline)
                ->latest('hutang.created_at')
                ->select('suppliers.nama_supplier', 'hutang.*')
                ->get();
            // Hitung total jumlah
            $totalJumlah = $hutang->sum('total_hutang');
            $content = view('admin.page.laporan', compact(['hutang', 'totalJumlah']))->render();
        }

        return view('admin.layout.blank_page', compact(['page', 'content']));
    }

    // HutangController.php
    public function destroy($id)
    {
        $hutang = Hutang::find($id);

        if ($hutang) {
            $hutang->delete();
            return response()->json(['message' => 'Data berhasil dihapus'], 200);
        }

        return response()->json(['message' => 'Data tidak ditemukan'], 404);
    }
}
