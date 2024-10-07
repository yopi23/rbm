<?php

namespace App\Http\Controllers\FrontController;

use App\Http\Controllers\Controller;
use App\Models\DetailBarangPesanan;
use App\Models\DetailSparepartPesanan;
use App\Models\Handphone;
use App\Models\Pesanan;
use App\Models\Sparepart;
use Illuminate\Http\Request;

class PesananController extends Controller
{
    //
    public function pesan_produk(Request $request, $id)
    {
        $data = Handphone::findOrFail($id);
        $cart = session()->get('cart_produk');
        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $data->nama_barang,
                    "qty" => 1,
                    "price" => $data->harga_jual_barang,
                    "photo" => $data->foto_barang,
                    "kode_invite" => $request->kode_invite
                ]
            ];
            session()->put('cart_produk', $cart);
            return redirect()->route('cart')->with('success', 'Produk Berhasil Ditambahkan ke Keranjang');
        }
        if (isset($cart[$id])) {
            $cart[$id]['qty']++;
            session()->put('cart_produk', $cart);
            return redirect()->route('cart')->with('success', 'Produk Berhasil Ditambahkan ke Keranjang');
        }
        $cart[$id] = [
            "name" => $data->nama_barang,
            "qty" => 1,
            "price" => $data->harga_jual_barang,
            "photo" => $data->foto_barang,
            "kode_invite" => $request->kode_invite
        ];
        session()->put('cart_produk', $cart);
        return redirect()->route('cart')->with('success', 'Produk Berhasil Ditambahkan ke Keranjang');
    }
    public function pesan_sparepart(Request $request, $id)
    {
        $data = Sparepart::findOrFail($id);
        $cart = session()->get('cart_sparepart');
        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $data->nama_sparepart,
                    "qty" => 1,
                    "price" => $data->harga_ecer,
                    "photo" => $data->foto_sparepart,
                    "kode_invite" => $request->kode_invite
                ]
            ];
            session()->put('cart_sparepart', $cart);
            return redirect()->route('cart')->with('success', 'Sparepart Berhasil Ditambahkan ke Keranjang');
        }
        if (isset($cart[$id])) {
            $cart[$id]['qty']++;
            session()->put('cart_sparepart', $cart);
            return redirect()->route('cart')->with('success', 'Sparepart Berhasil Ditambahkan ke Keranjang');
        }
        $cart[$id] = [
            "name" => $data->nama_sparepart,
            "qty" => 1,
            "price" => $data->harga_ecer,
            "photo" => $data->foto_sparepart,
            "kode_invite" => $request->kode_invite
        ];
        session()->put('cart_sparepart', $cart);
        return redirect()->route('cart')->with('success', 'Sparepart Berhasil Ditambahkan ke Keranjang');
    }
    public function delete_produk_in_cart(Request $request, $id)
    {
        if ($request->id) {
            $cart = session()->get('cart_produk');
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart_produk', $cart);
            }
            return redirect()->back()->with('success', 'Produk Berhasil Dihapus dari Keranjang');
        }
    }
    public function delete_sparepart_in_cart(Request $request, $id)
    {
        if ($request->id) {
            $cart = session()->get('cart_sparepart');
            if (isset($cart[$id])) {
                unset($cart[$id]);
                session()->put('cart_sparepart', $cart);
            }
            return redirect()->back()->with('success', 'Sparepart Berhasil Dihapus dari Keranjang');
        }
    }
    public function cart(Request $request)
    {
        return view('front.keranjang', compact(['request']));
    }
    public function checkout(Request $request)
    {
        return view('front.checkout', compact(['request']));
    }
    public function buat_pesanan(Request $request)
    {
        $sparepart = session()->get('cart_sparepart');
        $produk = session()->get('cart_produk');

        if ($sparepart) {
            foreach ($sparepart as $id => $item) {
                $cek = Sparepart::findOrFail($id);
                if ($cek) {
                    $cekpesanan = Pesanan::where([
                        ['kode_owner', '=', $cek->kode_owner],
                        ['nama_pemesan', '=', $request->nama_pemesan],
                        ['no_telp', '=', $request->no_telp],
                        ['email', '=', $request->email],
                    ])->get()->first();
                    if (!$cekpesanan) {
                        $kode_pesanan = 'PS' . date('ymdhis') . rand(500, 900);
                        $create = Pesanan::create([
                            'tgl_pesanan' => date('Y-m-d h:i:s'),
                            'kode_pesanan' => $kode_pesanan,
                            'kode_owner' => $cek->kode_owner,
                            'nama_pemesan' => $request->nama_pemesan,
                            'no_telp' => $request->no_telp,
                            'email' => $request->email,
                            'alamat' => $request->alamat != null ? $request->alamat : '-',
                            'catatan_pesanan' => $request->catatan_pesanan != null ? $request->catatan_pesanan : '-',
                        ]);
                        if ($create) {
                            $cekpesanan = Pesanan::where([
                                ['kode_owner', '=', $cek->kode_owner],
                                ['nama_pemesan', '=', $request->nama_pemesan],
                                ['no_telp', '=', $request->no_telp],
                                ['email', '=', $request->email],
                            ])->get()->first();
                        }
                    }
                    DetailSparepartPesanan::create([
                        'kode_pesanan' => $cekpesanan->id,
                        'kode_sparepart' => $id,
                        'detail_modal_pesan' => $cek->harga_beli,
                        'detail_harga_pesan' => $cek->harga_jual,
                        'qty_sparepart' => $item['qty'],
                    ]);
                    unset($sparepart[$id]);
                    session()->put('cart_sparepart', $sparepart);
                }
            }
        }
        if ($produk) {
            foreach ($produk as $id => $item) {
                $cek = Handphone::findOrFail($id);
                if ($cek) {
                    $cekpesanan = Pesanan::where([
                        ['kode_owner', '=', $cek->kode_owner],
                        ['nama_pemesan', '=', $request->nama_pemesan],
                        ['no_telp', '=', $request->no_telp],
                        ['email', '=', $request->email],
                    ])->get()->first();
                    if (!$cekpesanan) {
                        $kode_pesanan = 'PS' . date('ymdhis') . rand(500, 900);
                        $create = Pesanan::create([
                            'tgl_pesanan' => date('Y-m-d h:i:s'),
                            'kode_pesanan' => $kode_pesanan,
                            'kode_owner' => $cek->kode_owner,
                            'nama_pemesan' => $request->nama_pemesan,
                            'no_telp' => $request->no_telp,
                            'email' => $request->email,
                            'alamat' => $request->alamat != null ? $request->alamat : '-',
                            'catatan_pesanan' => $request->catatan_pesanan != null ? $request->catatan_pesanan : '-',
                        ]);
                        if ($create) {
                            $cekpesanan = Pesanan::where([
                                ['kode_owner', '=', $cek->kode_owner],
                                ['nama_pemesan', '=', $request->nama_pemesan],
                                ['no_telp', '=', $request->no_telp],
                                ['email', '=', $request->email],
                            ])->get()->first();
                        }
                    }
                    DetailBarangPesanan::create([
                        'kode_pesanan' => $cekpesanan->id,
                        'kode_barang' => $id,
                        'detail_modal_pesan' => $cek->harga_beli_barang,
                        'detail_harga_pesan' => $cek->harga_jual_barang,
                        'qty_barang' => $item['qty'],
                    ]);
                    unset($produk[$id]);
                    session()->put('cart_produk', $produk);
                }
            }
        }
        return redirect()->route('cart')->with('success', 'Pesanan Telah Dibuat. Admin Akan Menghubungi Anda Secepatnya');
    }
}
