<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ListOrder;
use App\Models\Sparepart;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class OrderController extends Controller
{
    /**
     * Menampilkan daftar order yang ada
     */
    public function index()
    {
        $page = 'Pesanan';

        // Ambil semua pesanan
        $orders = Order::orderBy('created_at', 'desc')->get();

        // Generate kode order baru
        $kode_order = $this->generateOrderCode();

         // Ambil data supplier untuk dropdown di modal
         $suppliers = Supplier::where('kode_owner', $this->getThisUser()->id_upline)->get();

        // Generate view
        $content = view('admin.page.order.index', compact('orders', 'kode_order','suppliers'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
 * Update pesanan
 */
public function update(Request $request, $id)
{
    // Validasi input
    $request->validate([
        'kode_supplier' => 'required|exists:suppliers,id',
        'catatan' => 'nullable|string',
    ]);

    try {
        $order = Order::findOrFail($id);

        // Cek apakah pesanan masih dalam status draft
        if ($order->status_order !== 'draft') {
            return redirect()->route('order.show', $id)
                ->with('error', 'Pesanan sudah diproses dan tidak dapat diubah.');
        }

        // Update pesanan
        $order->update([
            'kode_supplier' => $request->kode_supplier,
            'catatan' => $request->catatan,
        ]);

        return redirect()->route('order.edit', $id)
            ->with('success', 'Pesanan berhasil diperbarui');
    } catch (\Exception $e) {
        return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
}

    /**
     * Membuat pesanan baru dan redirect ke halaman edit
     */
    public function create(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_supplier' => 'required|exists:suppliers,id',
            'catatan' => 'nullable|string',
        ]);

        // Generate kode order
        $kode_order = $this->generateOrderCode();

        // Buat order baru
        $order = Order::create([
            'kode_order' => $kode_order,
            'tanggal_order' => Carbon::now(),
            'kode_supplier' => $request->kode_supplier,
            'status_order' => 'draft',
            'catatan' => $request->catatan,
            'total_item' => 0,
            'user_input' => $this->getThisUser()->id,
            'kode_owner' => $this->getThisUser()->id_upline,
        ]);

        return redirect()->route('order.edit', $order->id)->with('success', 'Pesanan baru berhasil dibuat');
    }

    /**
     * Menampilkan form edit pesanan
     */
    public function edit($id)
    {
        $page = 'Edit Pesanan';

        $order = Order::findOrFail($id);

        // Cek apakah pesanan masih dalam status draft
        if ($order->status_order !== 'draft') {
            return redirect()->route('order.show', $id)
                ->with('error', 'Pesanan sudah diproses dan tidak dapat diedit lagi.');
        }

        // Ambil daftar item pesanan
        $listItems = ListOrder::where('order_id', $id)->get();

        // Ambil daftar supplier untuk dropdown
        $suppliers = Supplier::where('kode_owner', $this->getThisUser()->id_upline)->get();

        // Ambil daftar sparepart untuk autocomplete
        $spareparts = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
            ->orderBy('nama_sparepart')
            ->get();

        $existingItemIds = ListOrder::where('order_id', $id)
            ->whereNotNull('sparepart_id')
            ->pluck('sparepart_id')
            ->toArray();

        $lowStockItems = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
            ->where('stok_sparepart', '<=', 5)
            ->whereNotIn('id', $existingItemIds)
            ->orderBy('stok_sparepart')
            ->get();
        // Ambil daftar item dengan stok rendah untuk rekomendasi
        // $lowStockItems = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
        //     ->where('stok_sparepart', '<=', 10)
        //     ->orderBy('stok_sparepart')
        //     ->get();

        $content = view('admin.page.order.edit', compact(
            'order',
            'listItems',
            'suppliers',
            'spareparts',
            'lowStockItems'
        ))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menampilkan detail pesanan
     */
    public function show($id)
    {
        $page = 'Detail Pesanan';

        $order = Order::with(['listOrders', 'supplier'])->findOrFail($id);

        $content = view('admin.page.order.show', compact('order'))->render();

        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Menambahkan item ke pesanan
     */
    public function addItem(Request $request, $id)
    {
        \Log::info('Data Request Tambah Item:', $request->all());
        // Validasi input
        $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'sparepart_id' => 'nullable|exists:spareparts,id',
            'catatan_item' => 'nullable|string',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return back()->withErrors(['error' => 'Pesanan sudah diproses dan tidak dapat diubah.']);
            }

            // Tambahkan item ke daftar pesanan
            ListOrder::create([
                'order_id' => $id,
                'sparepart_id' => $request->sparepart_id,
                'nama_item' => $request->nama_item,
                'jumlah' => $request->jumlah,
                'status_item' => 'pending',
                'harga_perkiraan' => $request->harga_perkiraan,
                'catatan_item' => $request->catatan_item,
                'kode_owner' => $this->getThisUser()->id_upline,
                'user_input' => auth()->user()->id,
            ]);
            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $id)->count();
            $order->save();

            DB::commit();

            return redirect()->route('order.edit', $id)->with('success', 'Item berhasil ditambahkan ke pesanan');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menambahkan item dari rekomendasi stok rendah ke pesanan
     */
    public function addLowStockItem(Request $request, $id)
    {
        // Validasi input
        $request->validate([
            'sparepart_id' => 'required|exists:spareparts,id',
            'jumlah' => 'required|integer|min:1',
        ]);

        DB::beginTransaction();

        try {
            $order = Order::findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return back()->withErrors(['error' => 'Pesanan sudah diproses dan tidak dapat diubah.']);
            }

            // Ambil data sparepart
            $sparepart = Sparepart::findOrFail($request->sparepart_id);

            // Tambahkan item ke daftar pesanan
            ListOrder::create([
                'order_id' => $id,
                'sparepart_id' => $sparepart->id,
                'nama_item' => $sparepart->nama_sparepart,
                'jumlah' => $request->jumlah,
                'status_item' => 'pending',
                'harga_perkiraan' => $sparepart->harga_beli,
                'catatan_item' => 'Item stok rendah: ' . $sparepart->stok_sparepart . ' tersisa',
                'kode_owner' => $this->getThisUser()->id_upline,
                'user_input' => auth()->user()->id,
            ]);

            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $id)->count();
            $order->save();

            DB::commit();

            return redirect()->route('order.edit', $id)->with('success', 'Item stok rendah berhasil ditambahkan ke pesanan');
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error saat menyimpan item: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Perbarui item pesanan
     */
    public function updateItem(Request $request, $itemId)
    {
        // Validasi input
        $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'catatan_item' => 'nullable|string',
        ]);

        try {
            $item = ListOrder::findOrFail($itemId);

            // Cek apakah pesanan masih dalam status draft
            $order = Order::findOrFail($item->order_id);
            if ($order->status_order !== 'draft') {
                return back()->withErrors(['error' => 'Pesanan sudah diproses dan tidak dapat diubah.']);
            }

            // Update item
            $item->update([
                'nama_item' => $request->nama_item,
                'jumlah' => $request->jumlah,
                'harga_perkiraan' => $request->harga_perkiraan,
                'catatan_item' => $request->catatan_item,
            ]);

            return redirect()->route('order.edit', $item->order_id)
                ->with('success', 'Item pesanan berhasil diperbarui');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Hapus item dari pesanan
     */
    public function removeItem($itemId)
    {
        try {
            $item = ListOrder::findOrFail($itemId);
            $orderId = $item->order_id;

            // Cek apakah pesanan masih dalam status draft
            $order = Order::findOrFail($orderId);
            if ($order->status_order !== 'draft') {
                return back()->withErrors(['error' => 'Pesanan sudah diproses dan tidak dapat diubah.']);
            }

            // Hapus item
            $item->delete();

            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $orderId)->count();
            $order->save();

            return redirect()->route('order.edit', $orderId)
                ->with('success', 'Item berhasil dihapus dari pesanan');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Finalisasi pesanan
     */
    public function finalize($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return redirect()->route('order.show', $id)
                    ->with('info', 'Pesanan sudah diproses sebelumnya.');
            }

            // Cek apakah ada item dalam pesanan
            $itemCount = ListOrder::where('order_id', $id)->count();
            if ($itemCount === 0) {
                return back()->withErrors(['error' => 'Pesanan tidak dapat difinalisasi karena tidak ada item.']);
            }

            // Update status pesanan
            $order->status_order = 'menunggu_pengiriman';
            $order->save();

            return redirect()->route('order.show', $id)
                ->with('success', 'Pesanan berhasil difinalisasi dan siap dikirim ke supplier.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Konversi pesanan menjadi pembelian
     */
    public function convertToPurchase($id)
    {
        DB::beginTransaction();

        try {
            $order = Order::with('listOrders')->findOrFail($id);

            // Cek apakah pesanan sudah dalam status menunggu_pengiriman atau selesai
            if (!in_array($order->status_order, ['menunggu_pengiriman', 'selesai'])) {
                return back()->withErrors(['error' => 'Pesanan harus difinalisasi terlebih dahulu.']);
            }

            // Generate kode pembelian baru
            $lastPembelian = DB::table('pembelians')->orderBy('id', 'desc')->first();
            $kode_pembelian = 'PB-' . date('Ymd') . '-';

            if ($lastPembelian) {
                $lastNumber = intval(Str::substr($lastPembelian->kode_pembelian, -3));
                $kode_pembelian .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
            } else {
                $kode_pembelian .= '001';
            }

            // Buat pembelian baru
            $pembelian = DB::table('pembelians')->insertGetId([
                'kode_pembelian' => $kode_pembelian,
                'tanggal_pembelian' => date('Y-m-d'),
                'supplier' => $order->supplier ? $order->supplier->nama_supplier : null,
                'keterangan' => "Dibuat dari pesanan #" . $order->kode_order,
                'total_harga' => 0,
                'status' => 'draft',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $totalHarga = 0;

            // Tambahkan semua item dari pesanan ke pembelian
            foreach ($order->listOrders as $item) {
                $hargaBeli = $item->harga_perkiraan ?: 0;

                // Jika item memiliki sparepart_id, gunakan harga beli dari sparepart
                if ($item->sparepart_id) {
                    $sparepart = Sparepart::find($item->sparepart_id);
                    if ($sparepart) {
                        $hargaBeli = $sparepart->harga_beli;
                    }
                }

                $itemTotal = $hargaBeli * $item->jumlah;
                $totalHarga += $itemTotal;

                // Tambahkan ke detail pembelian
                DB::table('detail_pembelians')->insert([
                    'pembelian_id' => $pembelian,
                    'sparepart_id' => $item->sparepart_id,
                    'nama_item' => $item->nama_item,
                    'jumlah' => $item->jumlah,
                    'harga_beli' => $hargaBeli,
                    'total' => $itemTotal,
                    'is_new_item' => $item->sparepart_id ? false : true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            // Update total harga pembelian
            DB::table('pembelians')
                ->where('id', $pembelian)
                ->update(['total_harga' => $totalHarga]);

            // Update status pesanan
            $order->status_order = 'selesai';
            $order->save();

            DB::commit();

            return redirect()->route('pembelian.edit', $pembelian)
                ->with('success', 'Pesanan berhasil dikonversi menjadi pembelian.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Batalkan pesanan
     */
    public function cancel($id)
    {
        try {
            $order = Order::findOrFail($id);

            // Cek apakah pesanan sudah selesai
            if ($order->status_order === 'selesai') {
                return back()->withErrors(['error' => 'Pesanan yang sudah selesai tidak dapat dibatalkan.']);
            }

            // Update status pesanan
            $order->status_order = 'dibatalkan';
            $order->save();

            return redirect()->route('order.index')
                ->with('success', 'Pesanan berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /**
     * Menghasilkan kode pesanan baru
     */
    private function generateOrderCode()
    {
        $lastOrder = Order::orderBy('id', 'desc')->first();
        $kode_order = 'ORD-' . date('Ymd') . '-';

        if ($lastOrder) {
            $lastNumber = intval(Str::substr($lastOrder->kode_order, -3));
            $kode_order .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_order .= '001';
        }

        return $kode_order;
    }
}
