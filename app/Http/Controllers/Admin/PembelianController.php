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
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Models\StockHistory;
use App\Models\StockNotification;
use App\Models\HargaKhusus;
use App\Models\Hutang;
use App\Models\ProductVariant;
use App\Models\Shift; // Import Shift
use App\Traits\ManajemenKasTrait;
use Illuminate\Support\Facades\Validator;
use App\Services\PriceCalculationService;
use App\Models\AttributeValue;
use App\Scopes\ActiveScope;

class PembelianController extends Controller
{
    use ManajemenKasTrait;

    private function getOwnerId(): int
    {
        $user = Auth::user();
        \Log::debug('User object:', [
        'user_id' => $user ? $user->id : null,
        'has_userDetail_method' => $user ? method_exists($user, 'userDetail') : false,
        'userDetail_relation' => $user ? $user->userDetail : null,
        'userDetail_loaded' => $user && $user->relationLoaded('userDetail')
    ]);
        if ($user->userDetail->jabatan == '1') {
            return $user->id;
        }
        return $user->userDetail->id_upline;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $page = 'Pembelian';
        // Ambil data pembelian untuk ditampilkan di tabel
        $pembelians = Pembelian::where('kode_owner', $this->getOwnerId())->orderBy('created_at', 'desc')->get();

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

        // Get Active Shift
        $shiftId = null;
        $activeShift = Shift::getActiveShift(Auth::id());
        if ($activeShift) {
            $shiftId = $activeShift->id;
        }

        // Buat pembelian baru
        $pembelian = Pembelian::create([
            'kode_pembelian' => $kode_pembelian,
            'tanggal_pembelian' => date('Y-m-d'),
            'total_harga' => 0,
            'kode_owner' => $this->getOwnerId(),
            'status' => 'draft',
            'shift_id' => $shiftId,
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

        $details = DetailPembelian::with([
            'productVariant.sparepart',
            'productVariant.attributeValues'
        ])->where('pembelian_id', $id)->get();

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

    // public function searchVariantsAjax(Request $request)
    // {
    //     // 1. Validasi input, pastikan ada parameter 'search'
    //     $searchTerm = $request->input('search');
    //     if (empty($searchTerm)) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Parameter pencarian diperlukan'
    //         ], 400);
    //     }

    //     // 2. Buat query ke model ProductVariant
    //     $variants = ProductVariant::query()
    //         // Eager load relasi untuk efisiensi dan agar datanya tersedia di frontend
    //         ->with(['sparepart', 'attributeValues.attribute'])

    //         // Lakukan pencarian di beberapa kolom relasi sekaligus
    //         ->where(function ($query) use ($searchTerm) {
    //             // Cari berdasarkan nama produk dasar (di tabel spareparts)
    //             $query->whereHas('sparepart', function ($q) use ($searchTerm) {
    //                 $q->where('nama_sparepart', 'LIKE', '%' . $searchTerm . '%');
    //             })
    //             // ATAU cari berdasarkan nilai atribut (di tabel attribute_values)
    //             ->orWhereHas('attributeValues', function ($q) use ($searchTerm) {
    //                 $q->where('value', 'LIKE', '%' . $searchTerm . '%');
    //             })
    //             // ATAU cari berdasarkan SKU varian itu sendiri
    //             ->orWhere('sku', 'LIKE', '%' . $searchTerm . '%');
    //         })
    //         // Ambil hanya 10 hasil teratas untuk performa
    //         ->take(10)
    //         ->get();

    //     // 3. Kembalikan hasil dalam format JSON yang diharapkan oleh frontend
    //     return response()->json([
    //         'success' => true,
    //         'results' => $variants
    //     ]);
    // }

    public function searchVariantsAjax(Request $request)
{
    try {
        $searchTerm = $request->input('search');
        if (empty($searchTerm)) {
            return response()->json([
                'success' => false,
                'message' => 'Parameter pencarian diperlukan'
            ], 400);
        }

        // Dapatkan ID owner yang dinamis
        $ownerId = $this->getThisUser()->id_upline;

        // 1. Pecah search term menjadi kata kunci (keywords)
        $normalizedInput = str_replace(',', '.', $searchTerm);
        $keywords = array_filter(explode(' ', strtolower($normalizedInput)));

        // Query untuk mencari varian
        $variants = ProductVariant::query()
            // Filter berdasarkan owner
            ->whereHas('sparepart', function ($q) use ($ownerId) {
                $q->withoutGlobalScope(ActiveScope::class)->where('kode_owner', $ownerId);
            })
            // Eager loading untuk relasi (ini sudah benar)
            ->with(['sparepart' => function($q) {
                $q->withoutGlobalScope(ActiveScope::class);
            }, 'attributeValues.attribute'])

            // =================================================================
            // ✅ PERBAIKAN LOGIKA PENCARIAN AKURAT DI SINI
            // =================================================================
            ->where(function ($query) use ($keywords) {

                // KONDISI 1 (ATAU): Semua keyword ada di NAMA SPAREPART
                $query->orWhere(function ($nameQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        // Pola REGEXP untuk kata utuh
                        $pattern = '\\b' . preg_quote($keyword, '/') . '\\b';
                        $nameQuery->whereHas('sparepart', function ($subQ) use ($pattern) {
                            // Normalisasi kolom nama sparepart sebelum membandingkan
                            $subQ->withoutGlobalScope(ActiveScope::class)->where(DB::raw("REPLACE(LOWER(nama_sparepart), ',', '.')"), 'REGEXP', $pattern);
                        });
                    }
                });

                // KONDISI 2 (ATAU): Semua keyword ada di NILAI ATRIBUT
                $query->orWhere(function ($attributeQuery) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        $pattern = '\\b' . preg_quote($keyword, '/') . '\\b';
                        $attributeQuery->whereHas('attributeValues', function ($subQ) use ($pattern) {
                            $subQ->where(DB::raw('LOWER(value)'), 'REGEXP', $pattern);
                        });
                    }
                });

                // (Opsional) KONDISI 3 (ATAU): Semua keyword ada di SKU
                // $query->orWhere(function ($skuQuery) use ($keywords) {
                //     foreach ($keywords as $keyword) {
                //         $skuQuery->where(DB::raw('LOWER(sku)'), 'LIKE', '%' . $keyword . '%');
                //     }
                // });

            })
            // =================================================================
            // AKHIR PERBAIKAN
            // =================================================================

            ->take(10) // Batasi hasil untuk AJAX
            ->get();

        // Tidak perlu transformasi di sini jika Anda ingin mengembalikan objek Eloquent lengkap
        // $variants = $variants->map(function ($variant) { ... });

        return response()->json([
            'success' => true,
            'results' => $variants // Kirim objek Eloquent lengkap dengan relasinya
        ]);

    } catch (\Exception $e) {
        \Log::error('Search Variant AJAX Error: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
        return response()->json([
            'success' => false,
            'message' => 'Terjadi kesalahan internal pada server. Silakan cek log.'
        ], 500);
    }
}

    /**
     * Method helper untuk pencarian sparepart
     */
    private function searchSpareparts($searchTerm)
{
    // try {
    //     // Baris ini tetap sama, mencoba mengambil data dari API terlebih dahulu
    //     $response = Http::get('/spareparts/search', [
    //         'search' => $searchTerm
    //     ]);

    //     if ($response->successful() && $response->json('status') === 'success') {
    //         return $response->json('data');
    //     }
    // } catch (\Exception $e) {
    //     // Jika API gagal, blok ini akan dieksekusi
    // }

    // Fallback ke query database langsung jika API gagal atau tidak berhasil
    $keywords = array_filter(explode(' ', strtolower(trim($searchTerm))));

    $query = DB::table('spareparts')
        // Tambahkan JOIN ke tabel kategori dan subkategori
        ->leftJoin('kategori_spareparts', 'spareparts.kode_kategori', '=', 'kategori_spareparts.id')
        ->leftJoin('sub_kategori_spareparts', 'spareparts.kode_sub_kategori', '=', 'sub_kategori_spareparts.id')
        ->where('spareparts.kode_owner', '=', $this->getThisUser()->id_upline);

    foreach ($keywords as $keyword) {
        $query->where(function ($q) use ($keyword) {
            // Menambahkan nama tabel untuk menghindari error kolom ambigu
            $q->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', '%' . $keyword . '%')
                ->orWhere(DB::raw('LOWER(spareparts.kode_sparepart)'), 'LIKE', '%' . $keyword . '%');
        });
    }

    $spareparts = $query->select([
        // Pilih kolom yang dibutuhkan dari semua tabel
        'spareparts.id',
        'spareparts.kode_sparepart',
        'spareparts.nama_sparepart',
        'spareparts.harga_beli',
        'spareparts.harga_jual',
        'spareparts.harga_ecer',
        'spareparts.harga_pasang',
        'spareparts.stok_sparepart',
        'spareparts.kode_kategori',
        'spareparts.kode_sub_kategori',
        'kategori_spareparts.nama_kategori', // Ambil nama kategori
        'sub_kategori_spareparts.nama_sub_kategori' // Ambil nama subkategori
    ])->take(10)->get(); // Tambahkan take() untuk membatasi hasil

    // Map hasil agar konsisten dengan yang diharapkan oleh frontend
    // Ini juga akan memastikan frontend menerima 'harga_khusus'
    return $spareparts->map(function ($item) {
        return [
            'id' => $item->id,
            'nama_sparepart' => $item->nama_sparepart,
            'stok_sparepart' => $item->stok_sparepart,
            'harga_beli' => $item->harga_beli,
            'harga_jual' => $item->harga_jual,
            'harga_ecer' => $item->harga_ecer,
            'harga_pasang' => $item->harga_pasang,
            'kode_kategori' => $item->kode_kategori,
            'kode_sub_kategori' => $item->kode_sub_kategori,
            'nama_kategori' => $item->nama_kategori,
            'nama_sub_kategori' => $item->nama_sub_kategori,
            'harga_khusus' => [], // Placeholder, karena query ini tidak join ke harga khusus
        ];
    });
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
    // 1. VALIDASI LENGKAP - (Tidak ada perubahan di sini)
    $validated = $request->validate([
        'nama_item' => 'required|string',
        'jumlah' => 'required|integer|min:1',
        'harga_beli' => 'required|numeric|min:0',
        'is_new_item' => 'required|boolean',
        'sparepart_id' => 'nullable|exists:spareparts,id',
        'product_variant_id' => 'nullable|exists:product_variants,id',
        'attributes' => 'nullable|array',
        'item_kategori' => 'nullable|exists:kategori_spareparts,id',
    ]);

    DB::beginTransaction();

    try {
        $pembelian = Pembelian::findOrFail($id);

        if ($pembelian->status === 'selesai') {
            return back()->withErrors(['error' => 'Pembelian sudah selesai dan tidak dapat diubah.']);
        }

        // 2. PERSIAPAN DATA - Gunakan data dari $validated, bukan $request
        $detailData = [
            'pembelian_id' => $pembelian->id,
            'nama_item' => $validated['nama_item'],
            'jumlah' => $validated['jumlah'],
            'harga_beli' => $validated['harga_beli'],
            'total' => $validated['jumlah'] * $validated['harga_beli'],
            'is_new_item' => $validated['is_new_item'],
        ];

        // 3. LOGIKA PERCABANGAN - Gunakan data dari $validated
        if ($validated['is_new_item']) {
            // Jika ini adalah ITEM BARU...
            $detailData['item_kategori'] = $validated['item_kategori'];
            // Simpan pilihan atribut sebagai JSON
            $detailData['attributes'] = json_encode($validated['attributes'] ?? []);
        } else {
            // Jika ini adalah ITEM LAMA (restock)...
            $detailData['product_variant_id'] = $validated['product_variant_id'];
            $detailData['attributes'] = json_encode($validated['attributes'] ?? []);
            // Ambil sparepart_id dari varian untuk konsistensi
            $variant = \App\Models\ProductVariant::find($validated['product_variant_id']);
            if ($variant) {
                $detailData['sparepart_id'] = $variant->sparepart_id;
            }
        }

        // 4. PENYIMPANAN DATA
        DetailPembelian::create($detailData);

        // Update total harga pembelian
        $pembelian->total_harga += $detailData['total'];
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
        // Validasi input
        $validated = $request->validate([
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'harga_beli' => 'required|numeric|min:0',
            'product_variant_id' => 'nullable|exists:product_variants,id',
            'attributes' => 'nullable|array',
            'item_kategori' => 'nullable|exists:kategori_spareparts,id',
        ]);

        DB::beginTransaction();
        try {
            $detail = DetailPembelian::with('pembelian')->findOrFail($detailId);
            $pembelian = $detail->pembelian;

            if ($pembelian->status === 'selesai') {
                return back()->withErrors(['error' => 'Pembelian sudah selesai dan tidak dapat diubah.']);
            }

            $oldTotal = $detail->total;

            // Siapkan data dasar untuk diupdate
            $updateData = [
                'nama_item' => $validated['nama_item'],
                'jumlah' => $validated['jumlah'],
                'harga_beli' => $validated['harga_beli'],
                'total' => $validated['jumlah'] * $validated['harga_beli'],
            ];

            // Selalu update kategori dan atribut jika ada di request
            // Ini berlaku untuk item baru maupun restock yang diedit
            $updateData['item_kategori'] = $validated['item_kategori'];
            $updateData['attributes'] = json_encode($validated['attributes'] ?? []);

            // Jika ini item lama (restock), pastikan variant ID tetap ada
            if (!$detail->is_new_item) {
                $updateData['product_variant_id'] = $validated['product_variant_id'];
                // Opsi: sesuaikan sparepart_id jika varian berubah total
                $variant = \App\Models\ProductVariant::find($validated['product_variant_id']);
                if($variant) {
                    $updateData['sparepart_id'] = $variant->sparepart_id;
                }
            }

            // Lakukan update
            $detail->update($updateData);

            // Update total harga pembelian
            $pembelian->total_harga = ($pembelian->total_harga - $oldTotal) + $updateData['total'];
            $pembelian->save();

            DB::commit();
            return redirect()->route('pembelian.edit', $pembelian->id)->with('success', 'Item berhasil diperbarui.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Terjadi kesalahan saat update item: ' . $e->getMessage()]);
        }
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
    public function finalize($id, PriceCalculationService $priceCalculator)
    {
        // Ambil data dari form yang disubmit
        $metodePembayaran = request('metode_pembayaran', 'Lunas');
        $tglJatuhTempo = request('tgl_jatuh_tempo');
        $supplierId = request('supplier');

        // Mulai transaksi database untuk memastikan semua proses berhasil atau tidak sama sekali
        DB::beginTransaction();

        try {
            // Validasi dasar sebelum memulai proses
            $pembelian = Pembelian::with('detailPembelians')->findOrFail($id);

            if ($pembelian->status === 'selesai') {
                return redirect()->route('pembelian.index')->with('info', 'Pembelian ini sudah diselesaikan sebelumnya.');
            }

            if ($pembelian->detailPembelians->isEmpty()) {
                return back()->withErrors(['error' => 'Pembelian tidak dapat diselesaikan karena tidak ada item.']);
            }

            $supplier = Supplier::find($supplierId);
            if (!$supplier) {
                throw new \Exception('Supplier tidak valid. Mohon pilih supplier terlebih dahulu.');
            }

            // Loop melalui setiap item detail dalam pembelian
            foreach ($pembelian->detailPembelians as $detail) {
                $sparepart = null;
                $variant = null;

                if ($detail->is_new_item) {
                    // ===============================================
                    // PROSES UNTUK ITEM BARU (MEMBUAT VARIAN BARU)
                    // ===============================================

                    // 1. Ambil ID nilai atribut dari detail.
                    //    Asumsi: Anda menyimpan pilihan atribut sebagai JSON di kolom `attributes` pada tabel `detail_pembelians`
                    $tempAttributeValueIds = json_decode($detail->attributes, true) ?: [];
                    $attributeValueIds = array_filter($tempAttributeValueIds);

                    // 2. Panggil service untuk mendapatkan harga jual berdasarkan aturan terbaik
                    $calculatedPrices = $priceCalculator->calculate(
                        $detail->harga_beli,
                        $detail->item_kategori,
                        $attributeValueIds
                    );

                    // 3. JIKA TIDAK ADA ATURAN HARGA, gagalkan seluruh transaksi!
                    if (is_null($calculatedPrices)) {
                        throw new \Exception('Tidak ada aturan harga yang valid (baik umum maupun khusus) ditemukan untuk item: "' . $detail->nama_item . '". Silakan atur harga di menu Pengaturan Harga terlebih dahulu.');
                    }

                    // 4. Cari atau buat produk dasar (Sparepart)
                    $sparepart = Sparepart::firstOrCreate(
                        [
                            // Kunci untuk mencari: nama dan kategori harus sama
                            'nama_sparepart' => $detail->nama_item,
                            'kode_kategori' => $detail->item_kategori
                        ],
                        [
                            // Data ini hanya akan diisi jika produk dasar BARU dibuat
                            'kode_sparepart' => 'SP-' . date('YmdHis') . rand(100, 999),
                            'kode_owner' => $this->getOwnerId(),
                            'kode_spl' => $supplierId, // Menggunakan ID supplier dari pembelian
                            'foto_sparepart' => '-',
                            'desc_sparepart' => null,

                            // Harga & stok di-set 0 karena sekarang dikelola di level varian
                            'stok_sparepart' => $detail->jumlah,
                            'harga_beli' => $detail->harga_beli,
                            'harga_jual' => $calculatedPrices['internal_price'],
                            'harga_ecer' => $calculatedPrices['wholesale_price'],
                            'harga_pasang' => $calculatedPrices['default_service_fee']??0,

                            // Kolom lama yang mungkin sudah tidak relevan
                            'kode_sub_kategori' => null,
                            'stock_asli' => null,
                        ]
                    );


                    // 5. Buat Varian Produk baru dengan harga yang sudah dikalkulasi
                    $variant = $sparepart->variants()->create([
                        'purchase_price'  => $detail->harga_beli,
                        'stock'           => $detail->jumlah,
                        'wholesale_price' => $calculatedPrices['wholesale_price'],
                        'retail_price'    => $calculatedPrices['retail_price'],
                        'internal_price'  => $calculatedPrices['internal_price'],
                    ]);


                    // 6. Hubungkan varian dengan nilai atribut yang dipilih
                    if (!empty($attributeValueIds)) {
                        $variant->attributeValues()->sync(array_values($attributeValueIds));
                    }

                    // 7. Update detail pembelian untuk menyimpan ID varian yang baru dibuat
                    $detail->product_variant_id = $variant->id;
                    $detail->save();
                    $sparepart->save();
                    $this->updateHargaKhusus($sparepart, $calculatedPrices);

                    // Log stock change for NEW ITEM
                    $this->logStockChange(
                        $sparepart->id,
                        $detail->jumlah,
                        'purchase_stock',
                        $pembelian->id,
                        'Pembelian item baru: ' . $detail->nama_item,
                        Auth::id(),
                        0,
                        $detail->jumlah
                    );

                } else {
                // ===============================================
                // PROSES UNTUK ITEM RESTOCK (UPDATE VARIAN LAMA)
                // ===============================================
                $variant = ProductVariant::find($detail->product_variant_id);

                if ($variant) {
                    $sparepart = Sparepart::find($detail->sparepart_id);

                    // PERBAIKAN BAGIAN 1: Validasi dan Ambil Kategori dari Sparepart
                    // Pastikan sparepart ditemukan sebelum melanjutkan
                    if (!$sparepart) {
                        throw new \Exception('Data sparepart induk tidak ditemukan untuk item restock: "' . $detail->nama_item . '".');
                    }
                    // Ambil ID kategori dari sparepart yang sudah ada
                    $categoryId = $sparepart->kode_kategori;

                    // (Logika attribute tetap ada untuk konsistensi, meskipun mungkin tidak digunakan pada restock)
                    $tempAttributeValueIds = json_decode($detail->attributes, true) ?: [];
                    $attributeValueIds = array_filter($tempAttributeValueIds);

                    // PERBAIKAN BAGIAN 2: Panggil Kalkulator dengan ID Kategori yang Benar
                    $calculatedPrices = $priceCalculator->calculate(
                        $detail->harga_beli,
                        $categoryId, // <-- Gunakan variabel $categoryId yang sudah kita dapatkan
                        $attributeValueIds
                    );

                    if (is_null($calculatedPrices)) {
                        throw new \Exception('Tidak ada aturan harga yang valid untuk item restock: "' . $detail->nama_item . '".');
                    }

                    // --- KALKULASI WEIGHTED AVERAGE COST (Tidak berubah) ---
                    $old_stock = $variant->stock;
                    $current_average_cost = $variant->purchase_price;
                    $new_stock_quantity = $detail->jumlah;
                    $new_stock_cost = $detail->harga_beli;

                    $total_old_value = $old_stock * $current_average_cost;
                    $total_new_value = $new_stock_quantity * $new_stock_cost;
                    $new_total_stock = $old_stock + $new_stock_quantity;

                    $new_average_cost = ($new_total_stock > 0)
                        ? ($total_old_value + $total_new_value) / $new_total_stock
                        : $new_stock_cost;

                    $variant->stock = $new_total_stock;
                    $variant->purchase_price = $new_average_cost;
                    $variant->wholesale_price = $calculatedPrices['wholesale_price'];
                    $variant->retail_price    = $calculatedPrices['retail_price'];
                    $variant->internal_price  = $calculatedPrices['internal_price'];
                    $variant->save();

                    if ($sparepart) {
                        $sparepart->stok_sparepart = $new_total_stock;
                        $sparepart->harga_beli = $new_stock_cost;
                        $sparepart->harga_pasang = $calculatedPrices['default_service_fee'] ?? 0;
                        $sparepart->save();

                        // Log stock change
                        $this->logStockChange(
                            $sparepart->id,
                            $detail->jumlah,
                            'purchase_stock',
                            $pembelian->id,
                            'Pembelian item: ' . $detail->nama_item,
                            Auth::id(),
                            $old_stock,
                            $new_total_stock
                        );
                    }
                } else {
                    throw new \Exception('Varian produk untuk restock tidak ditemukan untuk item: "' . $detail->nama_item . '".');
                }

                    $this->updateHargaKhusus($sparepart, $calculatedPrices);
                }
            }

            // Jika semua item berhasil diproses, update status pembelian
            $pembelian->supplier = $supplier->nama_supplier;
            $pembelian->status = 'selesai';
            $pembelian->metode_pembayaran = $metodePembayaran;

            // Update Shift ID to current active shift
            $activeShift = Shift::getActiveShift(Auth::id());
            if ($activeShift) {
                $pembelian->shift_id = $activeShift->id;
            }

            if ($metodePembayaran == 'Hutang') {
                $pembelian->status_pembayaran = 'Belum Lunas';
                $pembelian->tgl_jatuh_tempo = $tglJatuhTempo;

                // Buat catatan hutang baru
                Hutang::create([
                    'kode_supplier' => $supplier->id,
                    'kode_owner' => $this->getOwnerId(),
                    'kode_nota' => $pembelian->kode_pembelian,
                    'total_hutang' => $pembelian->total_harga,
                    'tgl_jatuh_tempo' => $tglJatuhTempo,
                    'status' => 'Belum Lunas',
                ]);

            } else { // Lunas
                $pembelian->status_pembayaran = 'Lunas';

                // Panggil Trait ManajemenKasTrait untuk mencatat pengeluaran
                $this->catatKas(
                    $pembelian,
                    0, // Debet (Pemasukan)
                    $pembelian->total_harga, // Kredit (Pengeluaran)
                    'Pembelian Lunas #' . $pembelian->kode_pembelian,
                    now()
                );
            }

            $pembelian->save();

            // Jika semua proses berhasil, simpan perubahan secara permanen
            DB::commit();

            return redirect()->route('pembelian.index')->with('success', 'Pembelian berhasil diselesaikan! Stok dan harga produk telah diperbarui.');

        } catch (\Exception $e) {
            // Jika terjadi error di manapun dalam blok try, batalkan semua perubahan
            DB::rollBack();

            // Tampilkan pesan error yang spesifik untuk debugging
            return back()->withErrors(['error' => 'GAGAL: ' . $e->getMessage()]);
        }
    }
    protected function updateHargaKhusus($sparepart, $calculatedPrices)
{
    HargaKhusus::updateOrCreate(
        ['id_sp' => $sparepart->id],
        [
            'harga_toko'   =>  0,
            'harga_satuan' => $calculatedPrices['retail_price'] ?? 0,
        ]
    );
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

    // Helper moved to StockHistoryTrait

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
