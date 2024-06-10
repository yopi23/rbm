<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Catatan;
use App\Models\DetailPartServices;
use App\Models\ListOrder;
use App\Models\PemasukkanLain;
use App\Models\PengeluaranOperasional;
use App\Models\PengeluaranToko;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use App\Models\Sevices as modelServices;
use App\Models\Sparepart;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Penarikan;


class DashboardController extends Controller
{
    //
    public function index(Request $request)
    {
        $page = "Dashboard";

        if ($this->getThisUser()->jabatan != '0') {
            // Penarikan

            $penarikanQuery = Penarikan::join('users', 'penarikans.kode_user', '=', 'users.id')
                ->where('penarikans.kode_owner', '=', $this->getThisUser()->id_upline)
                ->where('penarikans.created_at', '>=', now()->subMinutes(30)); // Memfilter data dalam satu jam terakhir

            $penarikan = $penarikanQuery->orderBy('penarikans.created_at', 'desc')
                ->take(2)
                ->get(['penarikans.id as id_penarikan', 'penarikans.*', 'users.name']);
            // dd($penarikan);
            // End Penarikan

            $service = modelServices::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $penjualan = Penjualan::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $sparepart = Sparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $kode_service = 'SV' . date('Ymd') . rand(500, 1000) . $service->count();
            $catatan = Catatan::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $pemasukkan_lain = PemasukkanLain::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $list_order = ListOrder::where('list_orders.kode_owner', '=', $this->getThisUser()->id_upline)
                ->get();
            $p1 = PengeluaranToko::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $p2 = PengeluaranOperasional::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
            $total_service = 0;
            $total_penjualan = 0;
            $total_pemasukkan_lain = 0;
            $total_pengeluaran = 0;
            foreach ($service as $item) {
                if ($item->tgl_service == date('Y-m-d')) {
                    if ($item->status_services == 'Diambil') {
                        $total_service += $item->total_biaya;
                    } else {
                        $total_service += $item->dp;
                    }
                }
            }
            foreach ($penjualan as $item) {
                if ($item->tgl_penjualan == date('Y-m-d') && $item->status_penjualan == '1') {
                    $total_penjualan += $item->total_penjualan;
                }
            }
            foreach ($pemasukkan_lain as $item) {
                if ($item->tgl_pemasukkan == date('Y-m-d')) {
                    $total_pemasukkan_lain += $item->jumlah_pemasukkan;
                }
            }
            foreach ($p1 as $item) {
                if ($item->tanggal_pengeluaran == date('Y-m-d')) {
                    $total_pengeluaran += $item->jumlah_pengeluaran;
                }
            }
            foreach ($p2 as $item) {
                if ($item->tgl_pengeluaran == date('Y-m-d')) {
                    $total_pengeluaran += $item->jml_pengeluaran;
                }
            }

            return view('admin.index', compact(['total_pengeluaran', 'list_order', 'total_pemasukkan_lain', 'total_service', 'total_penjualan', 'pemasukkan_lain', 'page', 'kode_service', 'sparepart', 'catatan', 'service', 'penjualan', 'supplier', 'penarikan']));
        }
        if ($this->getThisUser()->jabatan == '0') {
            $data = User::join('user_details', 'users.id', '=', 'user_details.kode_user')->where('user_details.jabatan', '=', '1')->get(['users.*', 'user_details.*', 'users.id as id_user']);
            return view('admin.index', compact(['page', 'data']));
        }
    }
    public function create_catatan(Request $request)
    {
        $create = Catatan::create([
            'tgl_catatan' => $request->tgl_catatan,
            'judul_catatan' => $request->judul_catatan,
            'catatan' => $request->catatan,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);
        if ($create) {
            return redirect()->back()->with('success', 'Tambah Catatan Berhasil');
        }
        return redirect()->back()->with('success', 'Tambah Catatan Gagal, Ada Kendala Teknis');
    }
    public function delete_catatan(Request $request, $id)
    {
        $data = Catatan::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Delete Catatan Berhasil');
        }
        return redirect()->back()->with('success', 'Delete Catatan Gagal, Ada Kendala Teknis');
    }
    public function create_list_order(Request $request)
    {
        $create = ListOrder::create([
            'tgl_order' => $request->tgl_order,
            'nama_order' => $request->nama_order,
            'catatan_order' => $request->catatan_order,
            'user_input' => auth()->user()->id,
            'kode_owner' => $this->getThisUser()->id_upline
        ]);
        if ($create) {
            return redirect()->back()->with('success', 'Tambah List Order Berhasil');
        }
        return redirect()->back()->with('success', 'Tambah Catatan Gagal, Ada Kendala Teknis');
    }
    public function delete_list_order(Request $request, $id)
    {
        $data = ListOrder::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Delete List Order Berhasil');
        }
        return redirect()->back()->with('success', 'Delete List Order Gagal, Ada Kendala Teknis');
    }


    public function create_pemasukkan_lain(Request $request)
    {
        $validate = $request->validate([
            'tgl_pemasukan' => ['required'],
            'jumlah_pemasukan' => ['required'],
        ]);
        if ($validate) {
            $create = PemasukkanLain::create([
                'tgl_pemasukkan' => $request->tgl_pemasukan,
                'judul_pemasukan' => $request->judul_pemasukan,
                'catatan_pemasukkan' => $request->catatan_pemasukan,
                'jumlah_pemasukkan' => $request->jumlah_pemasukan,
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if ($create) {
                return redirect()->back()->with('success', 'Tambah Pemasukkan Berhasil');
            }
            return redirect()->back()->with('error', 'Tambah Pemasukkan Gagal, Ada Kendala Teknis');
        }
    }
    public function delete_pemasukkan_lain(Request $request, $id)
    {
        $data = PemasukkanLain::findOrFail($id);
        $data->delete();
        if ($data) {
            return redirect()->back()->with('success', 'Pemasukkan Lain Berhasil Dihapus');
        }
    }
    public function create_service(Request $request)
    {
        $validate = $request->validate([
            'kode_service' => ['required'],
            'tgl_service' => ['required'],
        ]);
        if ($validate) {
            // Cek apakah kode service sudah ada dalam database
            $existingService = modelServices::where('kode_service', $request->kode_service)->first();

            if ($existingService) {
                return redirect()->back()->with('error', 'Kode Service sudah ada, pilih kode yang berbeda.');
            }
            $create = modelServices::create([
                'kode_service' => $request->kode_service,
                'tgl_service' => $request->tgl_service,
                'nama_pelanggan' => $request->nama_pelanggan,
                'no_telp' => $request->no_telp,
                'type_unit' => $request->type_unit,
                'keterangan' => $request->ket,
                'total_biaya' => $request->biaya_servis,
                'dp' => $request->dp,
                'status_services' => 'Antri',
                'kode_owner' => $this->getThisUser()->id_upline
            ]);
            if ($create) {
                if ($request->kode_sparepart != null) {
                    $data_service = modelServices::where([['kode_service', '=', $request->kode_service]])->get()->first();
                    for ($i = 0; $i < count($request->kode_sparepart); $i++) {
                        if ($request['kode_sparepart'][$i] != null) {
                            $update_sparepart = Sparepart::findOrFail($request['kode_sparepart'][$i]);
                            DetailPartServices::create([
                                'kode_services' => $data_service->id,
                                'kode_sparepart' => $request['kode_sparepart'][$i],
                                'detail_modal_part_service' => $update_sparepart->harga_beli,
                                'detail_harga_part_service' => $update_sparepart->harga_jual,
                                'qty_part' => $request['qty_kode_sparepart'][$i],
                                'user_input' => auth()->user()->id,
                            ]);
                            $stok_baru = $update_sparepart->stok_sparepart - $request['qty_kode_sparepart'][$i];
                            $update_sparepart->update([
                                'stok_sparepart' => $stok_baru,
                            ]);
                        }
                    }
                }
                return redirect()->back()->with('success', 'Tambah Service Berhasil');
            }
            return redirect()->back()->with('error', 'Tambah Service Gagal, Ada Kendala Teknis');
        }
    }
}
