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
use App\Services\FinancialService;

class LaporanController extends Controller
{
    protected $financialService;

    public function __construct(FinancialService $financialService)
    {
        $this->financialService = $financialService;
    }

    public function view_laporan(Request $request)
    {
        $page = "Laporan";

        if (isset($request->tgl_awal) && isset($request->tgl_akhir)) {
            $kode_owner = $this->getThisUser()->id_upline;
            $tgl_awal = $request->tgl_awal;
            $tgl_akhir = $request->tgl_akhir;

            // 1. MENGGUNAKAN MESIN PERHITUNGAN LABA TERPUSAT (TETAP)
            $labaResult = $this->financialService->calculateNetProfit($kode_owner, $tgl_awal, $tgl_akhir);

            // 2. MENGAMBIL KEMBALI SEMUA DATA DETAIL UNTUK TABEL RINCIAN
            // Service
            $service = DB::table('sevices')->join('profit_presentases', 'sevices.id', '=', 'profit_presentases.kode_service')->join('users', 'sevices.id_teknisi', '=', 'users.id')->where([['sevices.kode_owner', '=', $kode_owner], ['sevices.status_services', '=', 'Diambil'], ['sevices.updated_at', '>=', $tgl_awal . ' 00:00:00'], ['sevices.updated_at', '<=', $tgl_akhir . ' 23:59:59']])->select('sevices.*', 'profit_presentases.profit', 'users.name')->latest()->get();
            $part_toko_service = DetailPartServices::join('sevices', 'detail_part_services.kode_services', '=', 'sevices.id')->join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['sevices.kode_owner', '=', $kode_owner], ['sevices.status_services', '=', 'Diambil'], ['detail_part_services.created_at', '>=', $tgl_awal . ' 00:00:00'], ['detail_part_services.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.created_at as tgl_keluar', 'detail_part_services.*', 'spareparts.*', 'sevices.*', 'sevices.updated_at']);
            $part_luar_toko_service = DetailPartLuarService::join('sevices', 'detail_part_luar_services.kode_services', '=', 'sevices.id')->where([['sevices.kode_owner', '=', $kode_owner], ['sevices.status_services', '=', 'Diambil'], ['sevices.updated_at', '>=', $tgl_awal . ' 00:00:00'], ['sevices.updated_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_part_luar_services.id as id_part', 'detail_part_luar_services.updated_at as tgl_keluar', 'detail_part_luar_services.*', 'sevices.*']);
            $all_part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')->where([['spareparts.kode_owner', '=', $kode_owner]])->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);
            $all_part_luar_toko_service = DetailPartLuarService::latest()->get();

            // Penjualan
            $penjualan = Penjualan::where([['kode_owner', '=', $kode_owner], ['status_penjualan', '=', '1'],  ['created_at', '>=', $tgl_awal . ' 00:00:00'], ['created_at', '<=', $tgl_akhir . ' 23:59:59']])->latest()->get();
            $penjualan_sparepart = DetailSparepartPenjualan::join('penjualans', 'detail_sparepart_penjualans.kode_penjualan', '=', 'penjualans.id')->join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')->where([['penjualans.kode_owner', '=', $kode_owner], ['penjualans.status_penjualan', '=', '1'], ['detail_sparepart_penjualans.status_rf', '=', '0'], ['penjualans.created_at', '>=', $tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_sparepart_penjualans.created_at as tgl_keluar', 'detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*', 'penjualans.*']);
            $penjualan_barang = DetailBarangPenjualan::join('penjualans', 'detail_barang_penjualans.kode_penjualan', '=', 'penjualans.id')->join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')->where([['penjualans.kode_owner', '=', $kode_owner], ['penjualans.status_penjualan', '=', '1'], ['penjualans.created_at', '>=', $tgl_awal . ' 00:00:00'], ['penjualans.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_barang_penjualans.created_at as tgl_keluar', 'detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*', 'penjualans.*']);

            // Pesanan, Pengeluaran, Pemasukkan Lain, Penarikan
            $pesanan = Pesanan::where([['kode_owner', '=', $kode_owner], ['status_pesanan', '=', '2'], ['created_at', '>=', $tgl_awal . ' 00:00:00'], ['created_at', '<=', $tgl_akhir . ' 23:59:59']])->latest()->get();
            $sparepart_pesanan = DetailSparepartPesanan::join('pesanans', 'detail_sparepart_pesanans.kode_pesanan', '=', 'pesanans.id')->join('spareparts', 'detail_sparepart_pesanans.kode_sparepart', '=', 'spareparts.id')->where([['pesanans.kode_owner', '=', $kode_owner], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_sparepart_pesanans.created_at as tgl_keluar', 'detail_sparepart_pesanans.id as id_sparepart_pesanan', 'detail_sparepart_pesanans.*', 'spareparts.*', 'pesanans.*']);
            $barang_pesanan = DetailBarangPesanan::join('pesanans', 'detail_barang_pesanans.kode_pesanan', '=', 'pesanans.id')->join('handphones', 'detail_barang_pesanans.kode_barang', '=', 'handphones.id')->where([['pesanans.kode_owner', '=', $kode_owner], ['pesanans.status_pesanan', '=', '2'], ['pesanans.created_at', '>=', $tgl_awal . ' 00:00:00'], ['pesanans.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['detail_barang_pesanans.created_at as tgl_keluar', 'detail_barang_pesanans.id as id_barang_pesanan', 'detail_barang_pesanans.*', 'handphones.*', 'pesanans.*']);
            $pemasukkan_lain = PemasukkanLain::where([['kode_owner', '=', $kode_owner], ['created_at', '>=', $tgl_awal . ' 00:00:00'], ['created_at', '<=', $tgl_akhir . ' 23:59:59']])->latest()->get();
            $pengeluaran_toko = PengeluaranToko::where([['kode_owner', '=', $kode_owner], ['created_at', '>=', $tgl_awal . ' 00:00:00'], ['created_at', '<=', $tgl_akhir . ' 23:59:59']])->latest()->get();
            $pengeluaran_opx = PengeluaranOperasional::where([['kode_owner', '=', $kode_owner], ['created_at', '>=', $tgl_awal . ' 00:00:00'], ['created_at', '<=', $tgl_akhir . ' 23:59:59']])->latest()->get();
            $penarikan = Penarikan::join('user_details', 'penarikans.kode_user', '=', 'user_details.kode_user')->where([['penarikans.status_penarikan', '=', '1'], ['penarikans.kode_owner', '=', $kode_owner], ['penarikans.created_at', '>=', $tgl_awal . ' 00:00:00'], ['penarikans.created_at', '<=', $tgl_akhir . ' 23:59:59']])->get(['penarikans.id as id_penarikan', 'penarikans.*', 'user_details.*']);

            // 3. DATA RINGKASAN UNTUK INFO BOXES
            $omsetService = Sevices::where('kode_owner', $kode_owner)->where('status_services', 'Diambil')->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])->sum('total_biaya');
            $omsetPenjualan = Penjualan::where('kode_owner', $kode_owner)->where('status_penjualan', '1')->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])->sum('total_penjualan');
            $totalUangMuka = Sevices::where('kode_owner', $kode_owner)->whereBetween('created_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])->sum('dp');
            $totalPemasukkanLainnya = $pemasukkan_lain->sum('jumlah_pemasukkan');
            $totalPenarikan = $penarikan->sum('jumlah_penarikan');

            $labaServiceIds = Sevices::where('kode_owner', $kode_owner)->where('status_services', 'Diambil')->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])->pluck('id');
            $hppPartTokoService = DetailPartServices::whereIn('kode_services', $labaServiceIds)->sum(DB::raw('detail_modal_part_service * qty_part'));
            $hppPartLuarService = DetailPartLuarService::whereIn('kode_services', $labaServiceIds)->sum(DB::raw('harga_part * qty_part'));
            $labaKotorService = $omsetService - ($hppPartTokoService + $hppPartLuarService);

            $penjualanIds = Penjualan::where('kode_owner', $kode_owner)->where('status_penjualan', '1')->whereBetween('updated_at', [$tgl_awal . ' 00:00:00', $tgl_akhir . ' 23:59:59'])->pluck('id');
            $hppSparepartJual = DetailSparepartPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_sparepart'));
            $hppBarangJual = DetailBarangPenjualan::whereIn('kode_penjualan', $penjualanIds)->sum(DB::raw('detail_harga_modal * qty_barang'));
            $labaKotorPenjualan = $omsetPenjualan - ($hppSparepartJual + $hppBarangJual);

            // 4. MENGIRIM SEMUA DATA KE VIEW
            $content = view('admin.page.laporan', compact(
                'labaResult', 'omsetService', 'omsetPenjualan', 'totalUangMuka',
                'totalPemasukkanLainnya', 'totalPenarikan', 'labaKotorService', 'labaKotorPenjualan',
                'service', 'part_toko_service', 'part_luar_toko_service', 'all_part_toko_service',
                'all_part_luar_toko_service', 'penjualan', 'penjualan_sparepart', 'penjualan_barang',
                'pesanan', 'sparepart_pesanan', 'barang_pesanan', 'pemasukkan_lain', 'pengeluaran_toko',
                'pengeluaran_opx', 'penarikan', 'request'
            ))->render();

        } else {
            // Bagian ini tidak berubah
            $hutang = Hutang::join('suppliers', 'hutang.kode_supplier', '=', 'suppliers.id')->where('hutang.kode_owner', '=', $this->getThisUser()->id_upline)->latest('hutang.created_at')->select('suppliers.nama_supplier', 'hutang.*')->get();
            $totalJumlah = $hutang->sum('total_hutang');
            $content = view('admin.page.laporan', compact(['hutang', 'totalJumlah']))->render();
        }

        return view('admin.layout.blank_page', compact(['page', 'content']));
    }

    // Fungsi destroy biarkan seperti aslinya
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
