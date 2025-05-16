<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Pembelian;
use App\Models\DetailPembelian;
use App\Models\Sparepart;
use App\Models\Supplier;
use App\Models\KategoriSparepart;
use App\Models\SubKategoriSparepart;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\StockHistory;
use App\Models\StockNotification;

class PembelianController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $page = 'Pembelian';
        // Ambil data pembelian untuk ditampilkan di tabel
        $pembelians = Pembelian::orderBy('created_at', 'desc')->get();

        // Generate kode pembelian baru
        $lastPembelian = Pembelian::orderBy('id', 'desc')->first();
        $kode_pembelian = 'PB-' . date('Ymd') . '-';

        if ($lastPembelian) {
            $lastNumber = intval(Str::substr($lastPembelian->kode_pembelian, -3));
            $kode_pembelian .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_pembelian .= '001';
        }

        // Generate view dengan menggunakan blank_page layout
        $content = view('admin.page.pembelian.index', compact('pembelians', 'kode_pembelian'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Create pembelian baru dan langsung ke halaman tambah item
     */
    public function create()
    {
        // Generate kode pembelian baru
        $lastPembelian = Pembelian::orderBy('id', 'desc')->first();
        $kode_pembelian = 'PB-' . date('Ymd') . '-';

        if ($lastPembelian) {
            $lastNumber = intval(Str::substr($lastPembelian->kode_pembelian, -3));
            $kode_pembelian .= str_pad($lastNumber + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $kode_pembelian .= '001';
        }

        // Buat pembelian baru
        $pembelian = Pembelian::create([
            'kode_pembelian' => $kode_pembelian,
            'tanggal_pembelian' => date('Y-m-d'),
            'total_harga' => 0,
            'status' => 'draft', // Tambahkan status 'draft' untuk pembelian yang belum selesai
        ]);

        return redirect()->route('pembelian.edit', $pembelian->id);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, $id)
    {
        $page = 'Edit Pembelian';
        $kategori = KategoriSparepart::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $supplier = Supplier::where('kode_owner', '=', $this->getThisUser()->id_upline)->latest()->get();
        $pembelian = Pembelian::findOrFail($id);

        // Jika pembelian sudah selesai, redirect ke halaman detail
        if ($pembelian->status === 'selesai') {
            return redirect()->route('pembelian.show', $id)
                ->with('error', 'Pembelian sudah selesai dan tidak dapat diedit lagi.');
        }

        $details = DetailPembelian::where('pembelian_id', $id)->get();

        // Proses pencarian hanya jika ada parameter search
        $search_results = [];
        if ($request->has('search') && !empty($request->search)) {
            $search_results = $this->searchSpareparts($request->search);
        }

        $content = view('admin.page.pembelian.edit', compact('pembelian', 'details', 'search_results','kategori','supplier'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * API endpoint untuk pencarian sparepart via AJAX
     */
    public function searchSparepartsAjax(Request $request)
    {
        if ($request->has('search') && !empty($request->search)) {
            $search_results = $this->searchSpareparts($request->search);
            return response()->json([
                'success' => true,
                'results' => $search_results
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Parameter pencarian diperlukan'
        ]);
    }

    /**
     * Method helper untuk pencarian sparepart
     */
    private function searchSpareparts($searchTerm)
    {
        $search_results = [];

        try {
            // Gunakan URL endpoint API yang sesuai dengan sistem Anda
            $response = Http::get('/spareparts/search', [
                'search' => $searchTerm
            ]);

            // Jika response berhasil dan memiliki format yang diharapkan
            if ($response->successful() && $response->json('status') === 'success') {
                $search_results = $response->json('data');
            }
        } catch (\Exception $e) {
            // Fallback ke query database langsung jika API gagal
            $keywords = array_filter(explode(' ', strtolower(trim($searchTerm))));

            $query = DB::table('spareparts')
                ->where('kode_owner', '=', $this->getThisUser()->id_upline);

            foreach ($keywords as $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%')
                      ->orWhere(DB::raw('LOWER(kode_sparepart)'), 'LIKE', '%' . $keyword . '%');
                });
            }

            $search_results = $query->select([
                'id',
                'kode_sparepart',
                'nama_sparepart',
                'harga_beli',
                'harga_jual',
                'harga_ecer',
                'harga_pasang',
                'stok_sparepart',
                'created_at',
                'updated_at'
            ])->get()->toArray();
        }

        return $search_results;
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $page = 'Detail Pembelian';

        $pembelian = Pembelian::with('detailPembelians.sparepart')->findOrFail($id);

        $content = view('admin.page.pembelian.show', compact('pembelian'))->render();
        return view('admin.layout.blank_page', compact('page', 'content'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'tanggal_pembelian' => 'sometimes|date',
            'supplier' => 'sometimes|nullable|string|max:255',
            'keterangan' => 'sometimes|nullable|string',
        ]);

        try {
            $pembelian = Pembelian::findOrFail($id);

            // Jika pembelian sudah selesai, jangan izinkan update
            if ($pembelian->status === 'selesai') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pembelian sudah selesai dan tidak dapat diubah.'
                ], 403);
            }

            $pembelian->update($validated);

            return redirect()->route('pembelian.edit', $id)->with('success', 'Data berhasil diperbarui');
            // return response()->json(['success' => true, 'message' => 'Data berhasil diperbarui']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Tambah item ke pembelian
     */
    public function addItem(Request $request, $id)
    {
        $validated = $request->validate([
            'nama_item' => 'required|string',
            'jumlah' => 'required|integer|min:1',
            'harga_beli' => 'required|numeric|min:0',
            'harga_jual' => 'nullable|numeric|min:0', // Tetap validasi harga lain
            'harga_ecer' => 'nullable|numeric|min:0',
            'harga_pasang' => 'nullable|numeric|min:0',
            'is_new_item' => 'required|boolean',
            'sparepart_id' => 'nullable|exists:spareparts,id',
            'item_kategori' => 'nullable|exists:kategori_spareparts,id', // Tambahkan validasi kategori per item
            'item_sub_kategori' => 'nullable|exists:sub_kategori_spareparts,id',
        ]);

        DB::beginTransaction();

        try {
            $pembelian = Pembelian::findOrFail($id);

            // Jika pembelian sudah selesai, jangan izinkan penambahan item
            if ($pembelian->status === 'selesai') {
                return back()->withErrors(['error' => 'Pembelian sudah selesai dan tidak dapat diubah.']);
            }

            // Tambahkan detail pembelian (hanya dengan harga beli)
            $detail = DetailPembelian::create([
                'pembelian_id' => $pembelian->id,
                'sparepart_id' => $request->is_new_item ? null : $request->sparepart_id,
                'nama_item' => $request->nama_item,
                'jumlah' => $request->jumlah,
                'harga_beli' => $request->harga_beli,
                'harga_jual' => $request->harga_jual,
                'harga_ecer' => $request->harga_ecer,
                'harga_pasang' => $request->harga_pasang,
                'total' => $request->jumlah * $request->harga_beli,
                'is_new_item' => $request->is_new_item,
                'item_kategori' => $request->item_kategori, // Simpan kategori per item
                'item_sub_kategori' => $request->item_sub_kategori, // Simpan sub kategori per item
            ]);

            // Tambahkan harga lain jika ada
            // if ($request->has('harga_jual')) {
            //     $detailData['harga_jual'] = $request->harga_jual;
            // }
            // if ($request->has('harga_ecer')) {
            //     $detailData['harga_ecer'] = $request->harga_ecer;
            // }
            // if ($request->has('harga_pasang')) {
            //     $detailData['harga_pasang'] = $request->harga_pasang;
            // }
            // DetailPembelian::create($detailData);

            // Update total harga pembelian
            $pembelian->total_harga += $request->jumlah * $request->harga_beli;
            $pembelian->save();

            DB::commit();
            return redirect()->route('pembelian.edit', $id)->with('success', 'Item berhasil ditambahkan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function updateItem(Request $request, $detailId)
{
    $detail = DetailPembelian::findOrFail($detailId);

    // Validate input
    $request->validate([
        'nama_item' => 'required|string|max:255',
        'jumlah' => 'required|integer|min:1',
        'harga_beli' => 'required|numeric|min:0',
        'harga_jual' => 'nullable|numeric|min:0',
        'harga_ecer' => 'nullable|numeric|min:0',
        'harga_pasang' => 'nullable|numeric|min:0',
        'item_kategori' => 'nullable|exists:kategori_spareparts,id', // Validasi kategori
        'item_sub_kategori' => 'nullable|exists:sub_kategori_spareparts,id', // Validasi sub kategori
    ]);

    // Get the pembelian id from the detail
    $pembelianId = $detail->pembelian_id;

    // Calculate the old total
    $oldTotal = $detail->total;

    // Update the detail
    $detail->nama_item = $request->nama_item;
    $detail->jumlah = $request->jumlah;
    $detail->harga_beli = $request->harga_beli;
    $detail->harga_jual = $request->harga_jual;
    $detail->harga_ecer = $request->harga_ecer;
    $detail->harga_pasang = $request->harga_pasang;
    $detail->item_kategori = $request->item_kategori;
    $detail->item_sub_kategori = $request->item_sub_kategori;

    // Calculate new total
    $detail->total = $request->jumlah * $request->harga_beli;
    $detail->save();

    // Update pembelian total
    $pembelian = Pembelian::findOrFail($pembelianId);
    $pembelian->total_harga = $pembelian->total_harga - $oldTotal + $detail->total;
    $pembelian->save();

    return redirect()->route('pembelian.edit', $pembelianId)
        ->with('success', 'Item berhasil diperbarui');
}

    /**
     * Remove item dari pembelian
     */
    public function removeItem($id)
    {
        DB::beginTransaction();

        try {
            $detail = DetailPembelian::findOrFail($id);
            $pembelian_id = $detail->pembelian_id;

            $pembelian = Pembelian::findOrFail($pembelian_id);

            // Jika pembelian sudah selesai, jangan izinkan penghapusan item
            if ($pembelian->status === 'selesai') {
                return back()->withErrors(['error' => 'Pembelian sudah selesai dan tidak dapat diubah.']);
            }

            // Kurangi total harga pembelian
            $pembelian->total_harga -= $detail->total;
            $pembelian->save();

            // Hapus detail pembelian
            $detail->delete();

            DB::commit();
            return redirect()->route('pembelian.edit', $pembelian_id)->with('success', 'Item berhasil dihapus.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

   /**
 * Selesaikan pembelian dan update stok sparepart
 */
public function finalize($id)
{
    DB::beginTransaction();

    try {
        $pembelian = Pembelian::with('detailPembelians')->findOrFail($id);

        // Jika pembelian sudah selesai, jangan proses lagi
        if ($pembelian->status === 'selesai') {
            return redirect()->route('pembelian.index')->with('info', 'Pembelian sudah diselesaikan sebelumnya.');
        }

        // Validasi apakah ada detail pembelian
        if ($pembelian->detailPembelians->count() == 0) {
            return back()->withErrors(['error' => 'Pembelian tidak dapat diselesaikan karena tidak ada item.']);
        }

        $supplierId = request('supplier');
        $kategoriId = request('kategori');
        $subKategoriId = request('sub_kategori');

        // Get the actual objects for their codes
        $supplier = Supplier::find($supplierId);
        $kategori = KategoriSparepart::find($kategoriId);
        // Check if sub_kategori is provided and valid
        $subKategori = null;
        if ($subKategoriId) {
            $subKategori = SubKategoriSparepart::find($subKategoriId);
        }

        if (!$supplier || !$kategori) {
            return back()->withErrors(['error' => 'Supplier atau Kategori tidak valid. Mohon pilih supplier dan kategori terlebih dahulu.']);
        }

        // Update pembelian with supplier info
        $pembelian->supplier = $supplier->nama_supplier;
        $pembelian->update();

        // Proses semua item pembelian untuk update atau insert sparepart
        foreach ($pembelian->detailPembelians as $detail) {
            // Gunakan kategori dan sub kategori dari item detail
                $kategoriId = $detail->item_kategori;
                $subKategoriId = $detail->item_sub_kategori;

                // Validasi kategori
                if (!$kategoriId) {
                    DB::rollBack();
                    return back()->withErrors(['error' => 'Kategori tidak valid untuk item: ' . $detail->nama_item]);
                }

            do {
                $kode_sparepart = 'SP-' . date('Ymd') . '-' . rand(1000, 9999);
            } while (Sparepart::where('kode_sparepart', $kode_sparepart)->exists());

            if ($detail->is_new_item) {
                // Buat sparepart baru
                $sparepartData = [
                    'kode_sparepart' => $kode_sparepart,
                    'kode_kategori' => $kategoriId,
                    'kode_sub_kategori' => $subKategoriId, // Gunakan sub kategori per item
                    'kode_sub_kategori' => $subKategoriId,
                    'kode_spl' => $supplierId,
                    'nama_sparepart' => $detail->nama_item,
                    'stok_sparepart' => $detail->jumlah,
                    'harga_beli' => $detail->harga_beli,
                    'harga_jual' => $detail->harga_jual ?? ($detail->harga_beli * 1.2), // Default markup 20%
                    'harga_ecer' => $detail->harga_ecer ?? ($detail->harga_beli * 1.3), // Default markup 30%
                    'harga_pasang' => $detail->harga_pasang ?? ($detail->harga_beli * 1.4), // Default markup 40%
                    'kode_owner' => $this->getThisUser()->id_upline,
                    'foto_sparepart'=>'-',
                ];

                $sparepart = Sparepart::create($sparepartData);

                // Update detail pembelian dengan sparepart_id baru
                $detail->sparepart_id = $sparepart->id;
                $detail->save();

                // Catat di StockHistory - sparepart baru
                $this->logStockChange(
                    $sparepart->id,
                    $detail->jumlah,
                    'purchase',
                    $pembelian->kode_pembelian,
                    'Stok awal dari pembelian',
                    $this->getThisUser()->id
                );

            } else {
                // Update stok sparepart yang sudah ada
                if ($detail->sparepart_id) {
                    $sparepart = Sparepart::find($detail->sparepart_id);
                    if ($sparepart) {
                        // Simpan stok sebelum diupdate untuk dicatat di history
                        $stockBefore = $sparepart->stok_sparepart;

                        $sparepart->stok_sparepart += $detail->jumlah;
                        $sparepart->harga_beli = $detail->harga_beli;

                        // Update harga lain jika ada di detail
                        if (isset($detail->harga_jual)) {
                            $sparepart->harga_jual = $detail->harga_jual;
                        }
                        if (isset($detail->harga_ecer)) {
                            $sparepart->harga_ecer = $detail->harga_ecer;
                        }
                        if (isset($detail->harga_pasang)) {
                            $sparepart->harga_pasang = $detail->harga_pasang;
                        }
                        if ($detail->nama_item != $sparepart->nama_sparepart ) {
                            $sparepart->nama_sparepart = $detail->nama_item;
                        }
                         // Update kategori and sub-kategori if provided
                         if ($kategoriId) {
                            $sparepart->kode_kategori = $kategoriId;
                        }
                        if ($subKategoriId) {
                            $sparepart->kode_sub_kategori = $subKategoriId;
                        }

                        $sparepart->save();

                        // Catat di StockHistory - update sparepart yang sudah ada
                        $this->logStockChange(
                            $sparepart->id,
                            $detail->jumlah,
                            'purchase',
                            $pembelian->kode_pembelian,
                            'Penambahan stok dari pembelian',
                            $this->getThisUser()->id,
                            $stockBefore,
                            $sparepart->stok_sparepart
                        );

                        // Update status notifikasi stok rendah jika ada
                        $this->updateStockNotification($sparepart->id, $pembelian->kode_pembelian, $this->getThisUser()->id);
                    }
                }
            }
        }

        // Update status pembelian menjadi selesai
        $pembelian->status = 'selesai';
        $pembelian->save();

        DB::commit();
        return redirect()->route('pembelian.index')->with('success', 'Pembelian berhasil diselesaikan dan stok berhasil diupdate.');
    } catch (\Exception $e) {
        DB::rollBack();
        return back()->withErrors(['error' => 'Terjadi kesalahan: ' . $e->getMessage()]);
    }
}

public function getSubKategori($kategoriId)
{
    try {
        // Validasi kategori ID
        if (empty($kategoriId) || !is_numeric($kategoriId)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid category ID'
            ], 400);
        }

        // Log untuk debugging
        \Log::info('Fetching sub-categories for kategori ID: ' . $kategoriId);

        // Ambil sub kategori dengan eager loading kategori
        $subKategoris = SubKategoriSparepart::with('kategori')
                            ->where('kategori_id', $kategoriId)
                            ->where('kode_owner', $this->getThisUser()->id_upline)
                            ->get();

        // Log untuk debugging
        \Log::info('Found ' . $subKategoris->count() . ' sub-categories');

        return response()->json([
            'success' => true,
            'data' => $subKategoris,
            'message' => 'Sub categories retrieved successfully'
        ]);
    } catch (\Exception $e) {
        // Log error untuk debugging
        \Log::error('Error in getSubKategori: ' . $e->getMessage());
        \Log::error($e->getTraceAsString());

        return response()->json([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ], 500);
    }
}

/**
 * Helper method untuk mencatat perubahan stok
 */
private function logStockChange($sparepartId, $quantityChange, $referenceType, $referenceId, $notes = null, $userId, $stockBefore = 0, $stockAfter = null)
{
    // Jika stockAfter tidak disetel, hitung berdasarkan stockBefore + quantityChange
    if ($stockAfter === null) {
        $stockAfter = $stockBefore + $quantityChange;
    }

    // Buat log stock history
    StockHistory::create([
        'sparepart_id' => $sparepartId,
        'quantity_change' => $quantityChange,
        'reference_type' => $referenceType,
        'reference_id' => $referenceId,
        'stock_before' => $stockBefore,
        'stock_after' => $stockAfter,
        'notes' => $notes,
        'user_input' => $userId,
    ]);
}

/**
 * Helper method untuk update notifikasi stok rendah
 */
private function updateStockNotification($sparepartId, $purchaseId, $userId)
{
    $notification = StockNotification::where('sparepart_id', $sparepartId)
        ->where('status', 'pending')
        ->first();

    if ($notification) {
        $notification->status = 'processed';
        $notification->processed_by = $userId;
        $notification->processed_at = now();
        $notification->notes = 'Diproses melalui pembelian #' . $purchaseId;
        $notification->save();
    }
}
}
