<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\DetailBarangPenjualan;
use App\Models\DetailPartServices;
use App\Models\DetailSparepartPenjualan;
use App\Models\Garansi;
use App\Models\Handphone;
use App\Models\Penjualan;
use App\Models\Sparepart;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Redis;
use App\Traits\KategoriLaciTrait;
use PDO;

class PenjualanController extends Controller
{
    use KategoriLaciTrait;
    //
    public function view_penjualan(Request $request)
    {
        $page = "Penjualan";
        $listLaci = $this->getKategoriLaci();
        $data = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '=', '0']])->get()->first();
        $count = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline]])->get()->count();
        if (!$data) {
            $kode = 'TRX' . date('Ymd') . auth()->user()->id . $count;
            $create = Penjualan::create([
                'kode_penjualan' => $kode,
                'kode_owner' => $this->getThisUser()->id_upline,
                'nama_customer' => '-',
                'catatan_customer' => '',
                'total_bayar' => '0',
                'total_penjualan' => '0',
                'user_input' => auth()->user()->id,
                'status_penjualan' => '0',
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            if ($create) {
                $data = Penjualan::where([['user_input', '=', auth()->user()->id], ['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '=', '0']])->get()->first();
            }
        }
        // Mengambil data 5 hari terakhir
        $fiveDaysAgo = Carbon::now()->subDays(5)->startOfDay();

        $view_penjualan = Penjualan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_penjualan', '!=', '0']
        ])
            ->where('created_at', '>=', $fiveDaysAgo)
            ->latest()
            ->get();
        // $view_penjualan = Penjualan::where([['kode_owner', '=', $this->getThisUser()->id_upline], ['status_penjualan', '!=', '0']])->latest()->get();
        $view_barang = DetailBarangPenjualan::join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')->get(['detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*']);
        $view_sparepart = DetailSparepartPenjualan::join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')->get(['detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*']);
        $view_garansi = Garansi::where([['type_garansi', '=', 'penjualan']])->get();
        $garansi = Garansi::where([['type_garansi', '=', 'penjualan'], ['kode_garansi', '=', $data->kode_penjualan]])->get();
        $barang = DetailBarangPenjualan::join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')->where([['detail_barang_penjualans.kode_penjualan', '=', $data->id]])->get(['detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*']);
        $sparepart = DetailSparepartPenjualan::join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')->where([['detail_sparepart_penjualans.kode_penjualan', '=', $data->id]])->get(['detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*']);
        $all_sparepart = Sparepart::where([['kode_owner', '=', $this->getThisUser()->id_upline]])->latest()->get();
        $all_barang = Handphone::where([['kode_owner', '=', $this->getThisUser()->id_upline]])->latest()->get();
        $content = view('admin.page.penjualan', compact(['data', 'barang', 'sparepart', 'all_sparepart', 'all_barang', 'view_penjualan', 'view_barang', 'view_sparepart', 'view_garansi', 'garansi', 'listLaci']));
        return view('admin.layout.blank_page', compact(['page', 'content', 'listLaci']));
    }
    //Sparepart
    public function create_detail_sparepart(Request $request)
    {
        $cek = DetailSparepartPenjualan::where([['kode_penjualan', '=', $request->kode_penjualan], ['kode_sparepart', '=', $request->kode_sparepart]])->get()->first();
        if ($cek) {
            $qty_baru = $cek->qty_sparepart + $request->qty_sparepart;
            $cek->update([
                'qty_sparepart' => $qty_baru,
            ]);
            if ($cek) {
                $update = Sparepart::findOrFail($request->kode_sparepart);
                $stok_baru = $update->stok_sparepart - $request->qty_sparepart;
                $update->update([
                    'stok_sparepart' => $stok_baru,
                ]);
                return redirect()->back();
            }
        } else {
            $update = Sparepart::findOrFail($request->kode_sparepart);
            // Cek apakah ada harga kustom di request
            $hargaJual = $request->has('custom_harga') && $request->custom_harga > 0
                ? $request->custom_harga // Jika ada, gunakan harga kustom
                : $update->harga_ecer; // Jika tidak, gunakan harga jual default

            $create = DetailSparepartPenjualan::create([
                'kode_penjualan' => $request->kode_penjualan,
                'kode_sparepart' => $request->kode_sparepart,
                'detail_harga_modal' => $update->harga_beli,
                'detail_harga_jual' => $hargaJual,
                'qty_sparepart' => $request->qty_sparepart,
                'user_input' => auth()->user()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            if ($create) {
                $stok_baru = $update->stok_sparepart - $request->qty_sparepart;
                $update->update([
                    'stok_sparepart' => $stok_baru,
                ]);
                return redirect()->back();
            }
        }
    }
    public function delete_detail_sparepart(Request $request, $id)
    {
        $data = DetailSparepartPenjualan::findOrFail($id);
        $data->delete();
        if ($data) {
            $update = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update->stok_sparepart + $data->qty_sparepart;
            $update->update([
                'stok_sparepart' => $stok_baru,
            ]);
            return redirect()->back();
        }
    }
    //Barang
    public function create_detail_barang(Request $request)
    {
        $cek = DetailBarangPenjualan::where([['kode_penjualan', '=', $request->kode_penjualan], ['kode_barang', '=', $request->kode_barang]])->get()->first();
        if ($cek) {
            $qty_baru = $cek->qty_barang + $request->qty_barang;
            $cek->update([
                'qty_barang' => $qty_baru,
            ]);
            if ($cek) {
                $update = Handphone::findOrFail($request->kode_barang);
                $stok_baru = $update->stok_barang - $request->qty_barang;
                $update->update([
                    'stok_barang' => $stok_baru,
                ]);
                return redirect()->back();
            }
        } else {
            $update = Handphone::findOrFail($request->kode_barang);
            $create = DetailBarangPenjualan::create([
                'kode_penjualan' => $request->kode_penjualan,
                'kode_barang' => $request->kode_barang,
                'qty_barang' => $request->qty_barang,
                'detail_harga_modal' => $update->harga_beli_barang,
                'detail_harga_jual' => $update->harga_jual_barang,
                'user_input' => auth()->user()->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ]);
            if ($create) {

                $stok_baru = $update->stok_barang - $request->qty_barang;
                $update->update([
                    'stok_barang' => $stok_baru,
                ]);
                return redirect()->back();
            }
        }
    }
    public function delete_detail_barang(Request $request, $id)
    {
        $data = DetailBarangPenjualan::findOrFail($id);
        $data->delete();
        if ($data) {
            $update = Handphone::findOrFail($data->kode_barang);
            $stok_baru = $update->stok_barang + $data->qty_barang;
            $update->update([
                'stok_barang' => $stok_baru,
            ]);
            return redirect()->back();
        }
    }
    //Garansi

    public function store_garansi_penjualan(Request $request)
    {
        $create = Garansi::create([
            'type_garansi' => 'penjualan',
            'kode_garansi' => $request->kode_garansi,
            'nama_garansi' => $request->nama_garansi,
            'tgl_mulai_garansi' => $request->tgl_mulai_garansi,
            'tgl_exp_garansi' => $request->tgl_exp_garansi,
            'catatan_garansi' => $request->catatan_garansi != null ? $request->catatan_garansi : '-',
            'user_input' => auth()->user()->id,
            'kode_owner' => $this->getThisUser()->id_upline,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
        ]);
        if ($create) {
            return redirect()->back()->with([
                'success' => 'Garansi Ditambahkan'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops,Something Went Wrong'
        ]);
    }
    public function delete_garansi_penjualan(Request $request, $id)
    {
        $data = Garansi::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with([
                'success' => 'Garansi dihapus'
            ]);
        }
        return redirect()->back()->with([
            'error' => 'Oops,Something Went Wrong'
        ]);
    }

    public function edit(Request $request, $id)
    {
        $page = "Edit Data Penjualan";
        $all_sparepart = Sparepart::where([['kode_owner', '=', $this->getThisUser()->id_upline]])->latest()->get();
        $all_barang = Handphone::where([['kode_owner', '=', $this->getThisUser()->id_upline]])->latest()->get();
        $data = Penjualan::findOrFail($id);
        $garansi = Garansi::where([['type_garansi', '=', 'penjualan'], ['kode_garansi', '=', $data->kode_penjualan]])->get();
        $barang = DetailBarangPenjualan::join('handphones', 'detail_barang_penjualans.kode_barang', '=', 'handphones.id')->where([['detail_barang_penjualans.kode_penjualan', '=', $data->id]])->get(['detail_barang_penjualans.id as id_detail', 'detail_barang_penjualans.*', 'handphones.*']);
        $sparepart = DetailSparepartPenjualan::join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')->where([['detail_sparepart_penjualans.kode_penjualan', '=', $data->id]])->get(['detail_sparepart_penjualans.id as id_detail', 'detail_sparepart_penjualans.*', 'spareparts.*']);
        return view('admin.forms.penjualan', compact(['page', 'data', 'barang', 'sparepart', 'all_barang', 'all_sparepart', 'garansi']));
    }
    public function update(Request $request, $id)
    {
        $data = Penjualan::findOrFail($id);
        $data_update = [];
        if ($request->total_penjualan <= 0) return redirect()->route('penjualan')->with('error', 'Penjualan Tidak Boleh Kosong');
        if ($request->simpan == 'bayar') {
            $data_update = [
                'tgl_penjualan' => $request->tgl_penjualan,
                'nama_customer' => $request->nama_customer != null ? $request->nama_customer : '-',
                'catatan_customer' => $request->catatan_customer != null ? $request->catatan_customer : '-',
                'user_input' => auth()->user()->id,
                'status_penjualan' => '1',
                'total_penjualan' => $request->total_penjualan,
                'total_bayar' => $request->total_bayar,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];


            // laci
            // Misalnya, ambil kategori dari request
            $kategoriId = $request->input('id_kategorilaci');
            $uangMasuk = $request->input('total_penjualan');
            $keterangan = $request->input('nama_customer') . "-" . $request->input('catatan_customer');

            // Catat histori laci
            $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);
            //end laci
        }
        // Menangani form baru
        if ($request->simpan == 'newbayar') {
            $data_update = [
                'tgl_penjualan' => now(), // Atau ambil dari request jika ada
                'nama_customer' => $request->customer ?? '-', // Ambil dari form baru
                'catatan_customer' => $request->ket ?? '-',
                'user_input' => auth()->user()->id,
                'status_penjualan' => '1',
                'total_penjualan' => $request->total_penjualan,
                'total_bayar' => $request->bayar,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ];
            // laci
            // Misalnya, ambil kategori dari request
            $kategoriId = $request->input('id_kategorilaci');
            $uangMasuk = $request->input('total_penjualan');
            $keterangan = $request->input('nama_customer') . "-" . $request->input('catatan_customer');

            // Catat histori laci
            $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);
            //end laci
        }
        if ($request->simpan == 'simpan') {
            $data_update = [
                'tgl_penjualan' => $request->tgl_penjualan,
                'nama_customer' => $request->nama_customer != null ? $request->nama_customer : '-',
                'catatan_customer' => $request->catatan_customer != null ? $request->catatan_customer : '-',
                'user_input' => auth()->user()->id,
                'status_penjualan' => '2',
                'total_penjualan' => $request->total_penjualan,
                'total_bayar' => $request->total_bayar,
                'created_at' => Carbon::now(),
            ];
        }
        $data->update($data_update);
        if ($data) {
            return redirect()->back();
        }
    }

    // PenjualanController.php
    public function getDetailSparepart($id)
    {

        $detailsparepart = DetailSparepartPenjualan::join('spareparts', 'detail_sparepart_penjualans.kode_sparepart', '=', 'spareparts.id')
            ->where('detail_sparepart_penjualans.kode_penjualan', $id)
            ->get([

                'detail_sparepart_penjualans.id as id_detail',
                'detail_sparepart_penjualans.*',
                'spareparts.*'
            ]);

        $total_part_penjualan = 0;
        $totalitem = 0;

        foreach ($detailsparepart as $detailpart) {
            $totalitem += $detailpart->qty_sparepart;
            $total_part_penjualan += $detailpart->detail_harga_jual * $detailpart->qty_sparepart;
        }

        return response()->json([
            'totalitem' => $totalitem,
            'detailsparepart' => $detailsparepart,
            'total_part_penjualan' => $total_part_penjualan,
        ]);
    }
}
