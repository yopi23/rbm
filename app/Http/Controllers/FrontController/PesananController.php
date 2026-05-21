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
    private function _cartResponse($msg)
    {
        if (request()->ajax()) {
            $cartCount = 0;
            if(session('cart_produk')) $cartCount += count(session('cart_produk'));
            if(session('cart_sparepart')) $cartCount += count(session('cart_sparepart'));
            return response()->json([
                'success' => true,
                'message' => $msg,
                'cart_count' => $cartCount
            ]);
        }
        return redirect()->back()->with('success', $msg);
    }

    public function pesan_produk(Request $request, $id)
    {
        $data = Handphone::findOrFail($id);
        $cart = session()->get('cart_produk');
        
        $price = 0;
        if ($request->kode_invite) {
            $price = $data->harga_jual_barang;
        }

        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $data->nama_barang,
                    "qty" => 1,
                    "price" => $price,
                    "photo" => $data->foto_barang,
                    "kode_invite" => $request->kode_invite
                ]
            ];
            session()->put('cart_produk', $cart);
            return $this->_cartResponse('Produk Berhasil Ditambahkan ke Keranjang');
        }
        if (isset($cart[$id])) {
            $cart[$id]['qty']++;
            if ($request->kode_invite) {
                $cart[$id]['price'] = $price;
                $cart[$id]['kode_invite'] = $request->kode_invite;
            }
            session()->put('cart_produk', $cart);
            return $this->_cartResponse('Produk Berhasil Ditambahkan ke Keranjang');
        }
        $cart[$id] = [
            "name" => $data->nama_barang,
            "qty" => 1,
            "price" => $price,
            "photo" => $data->foto_barang,
            "kode_invite" => $request->kode_invite
        ];
        session()->put('cart_produk', $cart);
        return $this->_cartResponse('Produk Berhasil Ditambahkan ke Keranjang');
    }
    public function pesan_sparepart(Request $request, $id)
    {
        $data = Sparepart::findOrFail($id);
        $cart = session()->get('cart_sparepart');
        
        $price = 0;
        if ($request->kode_invite) {
            $price = $data->harga_ecer;
        }

        if (!$cart) {
            $cart = [
                $id => [
                    "name" => $data->nama_sparepart,
                    "qty" => 1,
                    "price" => $price,
                    "photo" => $data->foto_sparepart,
                    "kode_invite" => $request->kode_invite
                ]
            ];
            session()->put('cart_sparepart', $cart);
            return $this->_cartResponse('Sparepart Berhasil Ditambahkan ke Keranjang');
        }
        if (isset($cart[$id])) {
            $cart[$id]['qty']++;
            if ($request->kode_invite) {
                $cart[$id]['price'] = $price;
                $cart[$id]['kode_invite'] = $request->kode_invite;
            }
            session()->put('cart_sparepart', $cart);
            return $this->_cartResponse('Sparepart Berhasil Ditambahkan ke Keranjang');
        }
        $cart[$id] = [
            "name" => $data->nama_sparepart,
            "qty" => 1,
            "price" => $price,
            "photo" => $data->foto_sparepart,
            "kode_invite" => $request->kode_invite
        ];
        session()->put('cart_sparepart', $cart);
        return $this->_cartResponse('Sparepart Berhasil Ditambahkan ke Keranjang');
    }
    private function _cartStats() {
        $cart_produk = session('cart_produk');
        $cart_sparepart = session('cart_sparepart');
        $itemCount = 0;
        $totalPrice = 0;
        
        if($cart_produk) {
            foreach($cart_produk as $item) {
                $itemCount++;
                $totalPrice += $item['price'] * $item['qty'];
            }
        }
        if($cart_sparepart) {
            foreach($cart_sparepart as $item) {
                $itemCount++;
                $totalPrice += $item['price'] * $item['qty'];
            }
        }
        return [
            'count' => $itemCount,
            'total' => $totalPrice,
            'total_formatted' => number_format($totalPrice)
        ];
    }

    public function update_qty_produk(Request $request, $id) {
        $cart = session()->get('cart_produk');
        if(isset($cart[$id])) {
            if($request->action == 'plus') {
                $cart[$id]['qty']++;
            } else if($request->action == 'minus' && $cart[$id]['qty'] > 1) {
                $cart[$id]['qty']--;
            }
            session()->put('cart_produk', $cart);
            $stats = $this->_cartStats();
            return response()->json([
                'success' => true,
                'qty' => $cart[$id]['qty'],
                'item_subtotal_formatted' => number_format($cart[$id]['price'] * $cart[$id]['qty']),
                'cart_count' => $stats['count'],
                'cart_total_formatted' => $stats['total_formatted']
            ]);
        }
        return response()->json(['success' => false]);
    }

    public function update_qty_sparepart(Request $request, $id) {
        $cart = session()->get('cart_sparepart');
        if(isset($cart[$id])) {
            if($request->action == 'plus') {
                $cart[$id]['qty']++;
            } else if($request->action == 'minus' && $cart[$id]['qty'] > 1) {
                $cart[$id]['qty']--;
            }
            session()->put('cart_sparepart', $cart);
            $stats = $this->_cartStats();
            return response()->json([
                'success' => true,
                'qty' => $cart[$id]['qty'],
                'item_subtotal_formatted' => number_format($cart[$id]['price'] * $cart[$id]['qty']),
                'cart_count' => $stats['count'],
                'cart_total_formatted' => $stats['total_formatted']
            ]);
        }
        return response()->json(['success' => false]);
    }

    public function delete_produk_in_cart(Request $request, $id)
    {
        $cart = session()->get('cart_produk');
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart_produk', $cart);
        }
        if ($request->ajax()) {
            $stats = $this->_cartStats();
            return response()->json([
                'success' => true,
                'message' => 'Produk Berhasil Dihapus dari Keranjang',
                'cart_count' => $stats['count'],
                'cart_total_formatted' => $stats['total_formatted']
            ]);
        }
        return redirect()->back()->with('success', 'Produk Berhasil Dihapus dari Keranjang');
    }
    public function delete_sparepart_in_cart(Request $request, $id)
    {
        $cart = session()->get('cart_sparepart');
        if (isset($cart[$id])) {
            unset($cart[$id]);
            session()->put('cart_sparepart', $cart);
        }
        if ($request->ajax()) {
            $stats = $this->_cartStats();
            return response()->json([
                'success' => true,
                'message' => 'Sparepart Berhasil Dihapus dari Keranjang',
                'cart_count' => $stats['count'],
                'cart_total_formatted' => $stats['total_formatted']
            ]);
        }
        return redirect()->back()->with('success', 'Sparepart Berhasil Dihapus dari Keranjang');
    }
    public function cart(Request $request)
    {
        $isMember = false;
        $cart_sparepart = session()->get('cart_sparepart');
        $cart_produk = session()->get('cart_produk');
        
        if ($cart_sparepart) {
            foreach ($cart_sparepart as $item) {
                if (!empty($item['kode_invite'])) {
                    $isMember = true;
                    break;
                }
            }
        }
        if (!$isMember && $cart_produk) {
            foreach ($cart_produk as $item) {
                if (!empty($item['kode_invite'])) {
                    $isMember = true;
                    break;
                }
            }
        }

        $toko = \App\Models\TokoSetting::first();

        return view('front.keranjang', compact(['request', 'isMember', 'toko']));
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
