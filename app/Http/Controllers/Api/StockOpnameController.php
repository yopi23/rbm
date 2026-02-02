<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockOpnamePeriod;
use App\Models\StockOpnameDetail;
use App\Models\StockOpnameAdjustment;
use App\Models\Sparepart;
use App\Models\KategoriSparepart ;
use App\Models\Supplier ;
use App\Models\StockHistory;
use App\Models\Shift;
use App\Models\User;
use App\Models\ProductVariant;
use App\Models\HargaKhusus;
use App\Services\PriceCalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class StockOpnameController extends Controller
{
    /**
     * Mendapatkan daftar periode stock opname
     */
    public function getPeriods(Request $request)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $periods = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function($period) {
                    // Hitung progress
                    $totalItems = $period->details()->count();
                    $checkedItems = $period->details()->whereIn('status', ['checked', 'adjusted'])->count();
                    $progressPercentage = $totalItems > 0 ? round(($checkedItems / $totalItems) * 100) : 0;

                    return [
                        'id' => $period->id,
                        'kode_periode' => $period->kode_periode,
                        'nama_periode' => $period->nama_periode,
                        'tanggal_mulai' => $period->tanggal_mulai->format('Y-m-d'),
                        'tanggal_selesai' => $period->tanggal_selesai->format('Y-m-d'),
                        'status' => $period->status,
                        'status_text' => $period->status_text,
                        'total_items' => $totalItems,
                        'checked_items' => $checkedItems,
                        'progress' => $progressPercentage,
                        'created_at' => $period->created_at->format('Y-m-d H:i:s'),
                        'created_by' => $period->user ? $period->user->name : null
                    ];
                });

            return response()->json([
                'success' => true,
                'data' => $periods
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan detail periode stock opname
     */
    public function getPeriodDetail(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Hitung statistik
            $totalItems = $period->details()->count();
            $pendingCount = $period->details()->where('status', 'pending')->count();
            $checkedCount = $period->details()->where('status', 'checked')->count();
            $adjustedCount = $period->details()->where('status', 'adjusted')->count();
            $progressPercentage = $totalItems > 0 ? round((($checkedCount + $adjustedCount) / $totalItems) * 100) : 0;

            // Statistik selisih
            $positiveSelisih = $period->details()->where('selisih', '>', 0)->sum('selisih');
            $negativeSelisih = $period->details()->where('selisih', '<', 0)->sum('selisih');
            $itemsWithSelisih = $period->details()->whereNotNull('selisih')->where('selisih', '!=', 0)->count();

            $result = [
                'id' => $period->id,
                'kode_periode' => $period->kode_periode,
                'nama_periode' => $period->nama_periode,
                'tanggal_mulai' => $period->tanggal_mulai->format('Y-m-d'),
                'tanggal_selesai' => $period->tanggal_selesai->format('Y-m-d'),
                'status' => $period->status,
                'status_text' => $period->status_text,
                'catatan' => $period->catatan,
                'statistics' => [
                    'total_items' => $totalItems,
                    'pending_count' => $pendingCount,
                    'checked_count' => $checkedCount,
                    'adjusted_count' => $adjustedCount,
                    'progress' => $progressPercentage,
                    'positive_selisih' => $positiveSelisih,
                    'negative_selisih' => $negativeSelisih,
                    'items_with_selisih' => $itemsWithSelisih
                ],
                'created_at' => $period->created_at->format('Y-m-d H:i:s'),
                'created_by' => $period->user ? $period->user->name : null
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membuat periode stock opname baru
     */
    public function createPeriod(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_periode' => 'required|string|max:255',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $user = $this->getThisUser();
            $kode_owner = $user->id_upline;

            // Generate kode periode
            $prefix = 'SO-' . date('Ym') . '-';
            $lastPeriod = StockOpnamePeriod::where('kode_periode', 'like', $prefix . '%')
                ->orderBy('id', 'desc')
                ->first();

            if ($lastPeriod) {
                $lastNumber = (int) substr($lastPeriod->kode_periode, -3);
                $newNumber = $lastNumber + 1;
            } else {
                $newNumber = 1;
            }

            $kode_periode = $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);

            // Buat periode baru
            $period = StockOpnamePeriod::create([
                'kode_periode' => $kode_periode,
                'nama_periode' => $request->nama_periode,
                'tanggal_mulai' => $request->tanggal_mulai,
                'tanggal_selesai' => $request->tanggal_selesai,
                'status' => 'draft',
                'catatan' => $request->catatan,
                'user_input' => $user->id,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            // Generate detail opname untuk semua sparepart
            $spareparts = Sparepart::where('kode_owner', $kode_owner)->get();

            $detailsData = [];
            foreach ($spareparts as $sparepart) {
                $detailsData[] = [
                    'period_id' => $period->id,
                    'sparepart_id' => $sparepart->id,
                    'stock_tercatat' => $sparepart->stok_sparepart,
                    'status' => 'pending',
                    'kode_owner' => $kode_owner,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insert batch detail opname
            StockOpnameDetail::insert($detailsData);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Periode stock opname berhasil dibuat',
                'data' => [
                    'id' => $period->id,
                    'kode_periode' => $period->kode_periode,
                    'nama_periode' => $period->nama_periode,
                    'tanggal_mulai' => $period->tanggal_mulai->format('Y-m-d'),
                    'tanggal_selesai' => $period->tanggal_selesai->format('Y-m-d'),
                    'status' => $period->status,
                    'status_text' => $period->status_text,
                    'total_items' => $spareparts->count()
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memulai proses stock opname
     */
    public function startProcess(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Verifikasi status periode
            if ($period->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah selesai dan tidak dapat diubah.'
                ], 400);
            }

            if ($period->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah dibatalkan.'
                ], 400);
            }

            // Update status periode
            $period->status = 'in_progress';
            $period->save();

            return response()->json([
                'success' => true,
                'message' => 'Proses stock opname berhasil dimulai',
                'data' => [
                    'id' => $period->id,
                    'status' => $period->status,
                    'status_text' => $period->status_text
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan item yang belum diperiksa
     */
    public function getPendingItems(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $categoryId = $request->input('category_id'); // Tambahkan filter kategori
            $supplierId = $request->input('supplier_id'); // Tambahkan filter supplier

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            $query = $period->details()
                ->with(['sparepart', 'sparepart.kategori', 'sparepart.supplier']) // Tambahkan relasi kategori dan supplier
                ->where('status', 'pending');

            // Filter pencarian jika ada
            if (!empty($search)) {
                $query->whereHas('sparepart', function($q) use ($search) {
                    $q->where('kode_sparepart', 'like', "%{$search}%")
                    ->orWhere('nama_sparepart', 'like', "%{$search}%");
                });
            }

            // Filter kategori jika ada
            if (!empty($categoryId)) {
                $query->whereHas('sparepart', function($q) use ($categoryId) {
                    $q->where('kode_kategori', $categoryId);
                });
            }

            // Filter supplier jika ada
            if (!empty($supplierId)) {
                $query->whereHas('sparepart', function($q) use ($supplierId) {
                    $q->where('kode_spl', $supplierId);
                });
            }

            $pendingItems = $query->paginate($perPage);

            $result = [
                'current_page' => $pendingItems->currentPage(),
                'last_page' => $pendingItems->lastPage(),
                'per_page' => $pendingItems->perPage(),
                'total' => $pendingItems->total(),
                'items' => $pendingItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'sparepart_id' => $item->sparepart_id,
                        'kode_sparepart' => $item->sparepart->kode_sparepart,
                        'nama_sparepart' => $item->sparepart->nama_sparepart,
                        'kategori' => $item->sparepart->kategori ? $item->sparepart->kategori->nama_kategori : $item->sparepart->kode_kategori,
                        'supplier' => $item->sparepart->supplier ? $item->sparepart->supplier->nama_supplier : $item->sparepart->kode_spl,
                        'harga_beli' => $item->sparepart->harga_beli,
                        'stock_tercatat' => $item->stock_tercatat,
                        'status' => $item->status,
                        'status_text' => $item->status_text
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan item yang sudah diperiksa
     */
    public function getCheckedItems(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $perPage = $request->input('per_page', 10);
            $search = $request->input('search', '');
            $categoryId = $request->input('category_id'); // Tambahkan filter kategori
            $supplierId = $request->input('supplier_id'); // Tambahkan filter supplier

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            $query = $period->details()
                ->with(['sparepart', 'sparepart.kategori', 'sparepart.supplier']) // Tambahkan relasi kategori dan supplier
                ->whereIn('status', ['checked', 'adjusted']);

            // Filter pencarian jika ada
            if (!empty($search)) {
                $query->whereHas('sparepart', function($q) use ($search) {
                    $q->where('kode_sparepart', 'like', "%{$search}%")
                    ->orWhere('nama_sparepart', 'like', "%{$search}%");
                });
            }

            // Filter kategori jika ada
            if (!empty($categoryId)) {
                $query->whereHas('sparepart', function($q) use ($categoryId) {
                    $q->where('kode_kategori', $categoryId);
                });
            }

            // Filter supplier jika ada
            if (!empty($supplierId)) {
                $query->whereHas('sparepart', function($q) use ($supplierId) {
                    $q->where('kode_spl', $supplierId);
                });
            }

            $checkedItems = $query->paginate($perPage);

            $result = [
                'current_page' => $checkedItems->currentPage(),
                'last_page' => $checkedItems->lastPage(),
                'per_page' => $checkedItems->perPage(),
                'total' => $checkedItems->total(),
                'items' => $checkedItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'sparepart_id' => $item->sparepart_id,
                        'kode_sparepart' => $item->sparepart->kode_sparepart,
                        'nama_sparepart' => $item->sparepart->nama_sparepart,
                        'kategori' => $item->sparepart->kategori ? $item->sparepart->kategori->nama_kategori : $item->sparepart->kode_kategori,
                        'supplier' => $item->sparepart->supplier ? $item->sparepart->supplier->nama_supplier : $item->sparepart->kode_spl,
                        'stock_tercatat' => $item->stock_tercatat,
                        'stock_aktual' => $item->stock_aktual,
                        'selisih' => $item->selisih,
                        'status' => $item->status,
                        'status_text' => $item->status_text,
                        'catatan' => $item->catatan,
                        'checked_at' => $item->checked_at ? $item->checked_at->format('Y-m-d H:i:s') : null,
                        'user_check' => $item->userCheck ? $item->userCheck->name : null
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan daftar kategori untuk dropdown filter
     */
    public function getCategories()
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $categories = KategoriSparepart::where('kode_owner', $kode_owner)
                ->orWhereNull('kode_owner')
                ->select('id', 'nama_kategori')
                ->orderBy('nama_kategori')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan daftar supplier untuk dropdown filter
     */
    public function getSuppliers()
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $suppliers = Supplier::where('kode_owner', $kode_owner)
                ->orWhereNull('kode_owner')
                ->select('id','nama_supplier')
                ->orderBy('nama_supplier')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mencari sparepart berdasarkan kode/barcode
     */
    public function scanSparepart(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'search' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $search = $request->search;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Cari sparepart berdasarkan kode atau barcode
            $sparepart = Sparepart::where('kode_owner', $kode_owner)
                ->where(function($query) use ($search) {
                    $query->where('kode_sparepart', $search);
                        //   ->orWhere('barcode', $search);
                })
                ->first();

            if (!$sparepart) {
                // return response()->json([
                //     'success' => false,
                //     'message' => 'Sparepart tidak ditemukan'
                // ], 404);
                return response()->json([
                    'success' => true,
                    'type' => 'new_item',
                    'message' => 'Sparepart tidak ditemukan. Apakah ingin menambahkan sebagai item baru?',
                    'search_term' => $search
                ]);
            }

            // Cari detail opname untuk sparepart ini
            $detail = StockOpnameDetail::where('period_id', $id)
                ->where('sparepart_id', $sparepart->id)
                ->first();

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ini tidak terdaftar dalam periode stock opname ini'
                ], 404);
            }

            $result = [
                'id' => $detail->id,
                'sparepart_id' => $detail->sparepart_id,
                'kode_sparepart' => $sparepart->kode_sparepart,
                'nama_sparepart' => $sparepart->nama_sparepart,
                'kategori' => $sparepart->kode_kategori,
                'supplier' => $sparepart->kode_spl,
                'harga_beli' => $sparepart->harga_beli,
                'harga_jual' => $sparepart->harga_jual,
                'stock_tercatat' => $detail->stock_tercatat,
                'status' => $detail->status,
                'status_text' => $detail->status_text,
            ];

            if ($detail->status !== 'pending') {
                $result['stock_aktual'] = $detail->stock_aktual;
                $result['selisih'] = $detail->selisih;
                $result['catatan'] = $detail->catatan;
                $result['checked_at'] = $detail->checked_at ? $detail->checked_at->format('Y-m-d H:i:s') : null;
                $result['user_check'] = $detail->userCheck ? $detail->userCheck->name : null;
            }

            return response()->json([
                'success' => true,
                'data' => $result,
                'already_checked' => $detail->status !== 'pending'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    public function getAttributesByCategory($categoryId)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            // $kategori = KategoriSparepart::where('kode_owner', $kode_owner)
            //     ->orWhereNull('kode_owner')
            //     ->findOrFail($categoryId);
            $kategori = KategoriSparepart::where(function ($query) use ($kode_owner) {
                $query->where('kode_owner', $kode_owner)
                    ->orWhereNull('kode_owner');
            })->where('id', $categoryId)->firstOrFail();

            $attributes = $kategori->attributes()->with('values')->get();

            return response()->json([
                'success' => true,
                'data' => $attributes
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil atribut kategori: ' //. $e->getMessage()
            ], 500);
        }
    }


    // public function addNewItem(Request $request, $periodId)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'nama_sparepart' => 'required|string',
    //         'stock_aktual'   => 'required|integer|min:0',
    //         'harga_beli'     => 'nullable|numeric|min:0',
    //         'kode_kategori'  => 'required|exists:kategori_spareparts,id',
    //         'kode_supplier'  => 'required|exists:suppliers,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Validasi gagal',
    //             'errors'  => $validator->errors()
    //         ], 422);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         $user = $this->getThisUser();
    //         $kode_owner = $user->id_upline;

    //         // Generate kode unik
    //         do {
    //             $kode_sparepart = 'SP-' . date('Ymd') . '-' . rand(1000, 9999);
    //         } while (Sparepart::where('kode_sparepart', $kode_sparepart)->exists());

    //         // Buat sparepart baru
    //         $sparepart = Sparepart::create([
    //             'kode_sparepart' => $kode_sparepart,
    //             'nama_sparepart' => $request->nama_sparepart,
    //             'stok_sparepart' => $request->stock_aktual,
    //             'harga_beli'     => $request->harga_beli ?? 0,
    //             'harga_jual'     => ($request->harga_beli ?? 0) * 1.2, // Contoh markup 20%
    //             'harga_ecer'     => ($request->harga_beli ?? 0) * 1.3, // Contoh markup 30%
    //             'harga_pasang'   => ($request->harga_beli ?? 0) * 1.4, // Contoh markup 40%
    //             'kode_kategori'  => $request->kode_kategori,
    //             'kode_supplier'  => $request->kode_supplier,
    //             'kode_owner'     => $kode_owner,
    //             'foto_sparepart' => '-',
    //         ]);

    //         // Tambahkan ke detail stock opname
    //         $detail = StockOpnameDetail::create([
    //             'period_id' => $periodId,
    //             'sparepart_id' => $sparepart->id,
    //             'stock_tercatat' => $request->stock_aktual, // Sama dengan aktual karena baru
    //             'stock_aktual' => $request->stock_aktual,
    //             'selisih' => 0, // Tidak ada selisih untuk item baru
    //             'status' => 'adjusted', // Langsung adjusted karena tidak perlu penyesuaian
    //             'catatan' => 'Item baru ditambahkan selama stock opname',
    //             'user_check' => $user->id,
    //             'checked_at' => now(),
    //             'kode_owner' => $kode_owner,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'success' => true,
    //             'data'    => [
    //                 'id' => $detail->id,
    //                 'sparepart_id' => $sparepart->id,
    //                 'kode_sparepart' => $sparepart->kode_sparepart,
    //                 'nama_sparepart' => $sparepart->nama_sparepart,
    //                 'kategori' => $sparepart->kode_kategori,
    //                 'supplier' => $sparepart->kode_supplier,
    //                 'stock_tercatat' => $request->stock_aktual,
    //                 'stock_aktual' => $request->stock_aktual,
    //                 'selisih' => 0,
    //                 'status' => 'adjusted',
    //                 'already_checked' => true,
    //                 'current_stock' => $request->stock_aktual,
    //                 'new_stock_after_adjustment' => $request->stock_aktual
    //             ]
    //         ]);

    //     } catch (\Exception $e) {
    //         DB::rollBack();
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Gagal menambahkan item: ' . $e->getMessage()
    //         ], 500);
    //     }
    // }
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

    public function addNewItem(Request $request, $periodId, PriceCalculationService $priceCalculator)
    {
        Log::info('addNewItem: Request diterima', [
            'period_id' => $periodId,
            'request_data' => $request->all(),
        ]);

        $validator = Validator::make($request->all(), [
            'nama_sparepart' => 'required|string',
            'stock_aktual'   => 'required|integer|min:0',
            'harga_beli'     => 'nullable|numeric|min:0',
            'kode_kategori'  => 'required|exists:kategori_spareparts,id',
            'kode_supplier'  => 'required|exists:suppliers,id',
            'attributes'     => 'nullable|array',
        ]);

        if ($validator->fails()) {
            // ... (Log validasi gagal)
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction();

        try {
            $user = $this->getThisUser();
            $kode_owner = $user->id_upline;
            $hargaBeli = $request->harga_beli ?? 0;
            $rawAttributes = $request->input('attributes');
            $attributeValueIds = array_filter(is_array($rawAttributes) ? $rawAttributes : []);

            // 1. Hitung Harga Jual menggunakan PriceCalculationService
            $calculatedPrices = $priceCalculator->calculate(
                $hargaBeli,
                $request->kode_kategori,
                $attributeValueIds
            );

            Log::info('addNewItem: Memulai perhitungan harga', [
                'period_id' => $periodId,
                'user_id' => $user->id,
                'owner_id' => $kode_owner,
                'harga_beli' => $hargaBeli,
                'kode_kategori' => $request->kode_kategori,
                'attribute_ids' => $attributeValueIds,
            ]);

            if (is_null($calculatedPrices)) {
                throw new \Exception('Tidak ada aturan harga yang valid untuk item baru: "' . $request->nama_sparepart . '"');
            }

            // 2. Generate kode unik (seperti sebelumnya)
            do {
                $kode_sparepart = 'SP' . date('Ymd') .  rand(1000, 9999);
            } while (Sparepart::where('kode_sparepart', $kode_sparepart)->exists());

            // 3. Buat sparepart baru
            $sparepart = Sparepart::create([
                'kode_sparepart' => $kode_sparepart,
                'nama_sparepart' => $request->nama_sparepart,
                'stok_sparepart' => $request->stock_aktual,
                'harga_beli'     => $hargaBeli,
                'harga_jual'     => $calculatedPrices['internal_price'],
                'harga_ecer'     => $calculatedPrices['wholesale_price'],
                'harga_pasang'   => $calculatedPrices['default_service_fee'] ?? 0,
                'kode_kategori'  => $request->kode_kategori,
                'kode_spl'       => $request->kode_supplier,
                'kode_owner'     => $kode_owner,
                'foto_sparepart' => '-',
                'kode_sub_kategori' => null,
                'stock_asli' => null,
            ]);

            // LOG BARU: Pastikan Sparepart berhasil dibuat sebelum melanjutkan
            Log::info('addNewItem: Sparepart berhasil dibuat', [
                'sparepart_id' => $sparepart->id,
            ]);


            // 4. Buat Product Variant default untuk sparepart baru
            $variant = $sparepart->variants()->create([
                'purchase_price' => $hargaBeli,
                'stock' => $request->stock_aktual,
                'wholesale_price' => $calculatedPrices['wholesale_price'],
                'retail_price' => $calculatedPrices['retail_price'],
                'internal_price' => $calculatedPrices['internal_price'],
            ]);

            // TAMBAH: Sinkronisasi Atribut jika ada
            if (!empty($attributeValueIds)) {
                $variant->attributeValues()->sync(array_values($attributeValueIds));
            }

            // 5. Update Harga Khusus
            $this->updateHargaKhusus($sparepart, $calculatedPrices);

            // 6. Tambahkan ke detail stock opname (seperti sebelumnya)
            $detail = StockOpnameDetail::create([
                'period_id' => $periodId,
                'sparepart_id' => $sparepart->id,
                'stock_tercatat' => 0,
                'stock_aktual' => $request->stock_aktual,
                'selisih' => $request->stock_aktual,
                'status' => 'adjusted',
                'catatan' => 'Item baru ditemukan dan ditambahkan selama stock opname',
                'user_check' => $user->id,
                'checked_at' => now(),
                'kode_owner' => $kode_owner,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item baru berhasil ditambahkan dan disesuaikan',
                // ... (return data seperti sebelumnya)
                'data' => [
                    'id' => $detail->id,
                    'sparepart_id' => $sparepart->id,
                    'kode_sparepart' => $sparepart->kode_sparepart,
                    'nama_sparepart' => $sparepart->nama_sparepart,
                    'kategori' => $request->kode_kategori, // Ganti dengan ID kategori
                    'supplier' => $request->kode_supplier, // Ganti dengan ID supplier
                    'stock_tercatat' => 0,
                    'stock_aktual' => $request->stock_aktual,
                    'selisih' => $request->stock_aktual,
                    'status' => 'adjusted',
                    'already_checked' => true,
                    'current_stock' => $request->stock_aktual,
                    'new_stock_after_adjustment' => $request->stock_aktual
                ]
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            // PERBAIKAN LOG ERROR
            Log::error('addNewItem: GAGAL (Rollback) - Error Internal Server', [
                'period_id' => $periodId,
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'stack' => $e->getTraceAsString(), // Log stack trace lengkap
            ]);

            return response()->json([
                'success' => false,
                // Mengembalikan pesan error yang lebih spesifik untuk membantu debugging
                'message' => 'Gagal menambahkan item. Error: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Simpan hasil pemeriksaan stok
     */
    public function saveItemCheck(Request $request, $periodId, $detailId)
    {
        $validator = Validator::make($request->all(), [
            'stock_aktual' => 'required|integer|min:0',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $user = $this->getThisUser();
            $kode_owner = $user->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($periodId);

            $detail = StockOpnameDetail::with('sparepart.variants')
                ->where('period_id', $periodId)
                ->where('id', $detailId)
                ->first();

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan dalam periode ini'
                ], 404);
            }

            // Verifikasi status periode
            if (!in_array($period->status, ['in_progress', 'draft'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini tidak dalam status yang dapat diperiksa'
                ], 400);
            }

            // Verifikasi status detail
            if ($detail->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ini sudah diperiksa sebelumnya'
                ], 400);
            }

            $sparepart = $detail->sparepart;
            $stockTercatat = $detail->stock_tercatat;
            $stockAktual = $request->stock_aktual;
            $selisih = $stockAktual - $stockTercatat;

            // Update detail stock opname
            $detail->stock_aktual = $stockAktual;
            $detail->selisih = $selisih;
            $detail->status = 'adjusted'; // Langsung adjusted
            $detail->catatan = $request->catatan;
            $detail->user_check = $user->id;
            $detail->checked_at = now();
            $detail->save();

            // LANGSUNG UPDATE STOK SPAREPART & VARIANT
            if ($selisih != 0) {
                $currentStock = $sparepart->stok_sparepart;
                $newStock = $stockAktual; // Stok baru = stok aktual hasil pemeriksaan

                // Update Sparepart
                $sparepart->stok_sparepart = $newStock;
                $sparepart->save();

                // Update Variant
                $variant = $sparepart->variants->first();
                if ($variant) {
                    $variant->stock = $newStock;
                    $variant->save();
                }

                // Catat ke adjustment history untuk riwayat
                StockOpnameAdjustment::create([
                    'detail_id' => $detail->id,
                    'stock_before' => $currentStock,
                    'stock_after' => $newStock,
                    'adjustment_qty' => $selisih,
                    'alasan_adjustment' => $request->catatan ?? 'Penyesuaian otomatis dari pemeriksaan stock opname',
                    'user_input' => $user->id,
                    'kode_owner' => $kode_owner,
                ]);

                // Catat di stock history
                if (class_exists('App\Models\StockHistory')) {
                    $shiftId = null;
                    $activeShift = Shift::getActiveShift($user->id);
                    if ($activeShift) {
                        $shiftId = $activeShift->id;
                    }

                    StockHistory::create([
                        'sparepart_id' => $sparepart->id,
                        'quantity_change' => $selisih,
                        'reference_type' => 'stock_opname',
                        'reference_id' => $period->kode_periode,
                        'stock_before' => $currentStock,
                        'stock_after' => $newStock,
                        'notes' => 'Penyesuaian dari stock opname: ' . ($request->catatan ?? 'Tidak ada catatan'),
                        'user_input' => $user->id,
                        'shift_id' => $shiftId,
                    ]);
                }
            }

            // Update status periode jika semua item sudah diperiksa
            $pendingCount = $period->details()->where('status', 'pending')->count();
            if ($pendingCount === 0) {
                $period->status = 'completed';
                $period->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil diperiksa dan stok telah diperbarui',
                'data' => [
                    'id' => $detail->id,
                    'sparepart_id' => $detail->sparepart_id,
                    'stock_tercatat' => $detail->stock_tercatat,
                    'stock_aktual' => $detail->stock_aktual,
                    'selisih' => $detail->selisih,
                    'status' => $detail->status,
                    'status_text' => $detail->status_text,
                    'catatan' => $detail->catatan,
                    'checked_at' => $detail->checked_at->format('Y-m-d H:i:s'),
                    'period_status' => $period->status,
                    'period_status_text' => $period->status_text,
                    'new_stock' => $sparepart->stok_sparepart
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveItemCheck', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan detail item untuk adjustment
     */
    public function getAdjustmentDetail(Request $request, $periodId, $detailId)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($periodId);

            $detail = StockOpnameDetail::with(['sparepart', 'adjustments.user'])
                ->where('period_id', $periodId)
                ->where('id', $detailId)
                ->first();

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan dalam periode ini'
                ], 404);
            }

            // Verifikasi status
            if (!in_array($detail->status, ['checked', 'adjusted'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ini belum diperiksa atau tidak dapat disesuaikan'
                ], 400);
            }

            $result = [
                'id' => $detail->id,
                'sparepart_id' => $detail->sparepart_id,
                'kode_sparepart' => $detail->sparepart->kode_sparepart,
                'nama_sparepart' => $detail->sparepart->nama_sparepart,
                'kategori' => $detail->sparepart->kode_kategori,
                'supplier' => $detail->sparepart->kode_spl,
                'harga_beli' => $detail->sparepart->harga_beli,
                'stock_tercatat' => $detail->stock_tercatat,
                'stock_aktual' => $detail->stock_aktual,
                'selisih' => $detail->selisih,
                'status' => $detail->status,
                'status_text' => $detail->status_text,
                'catatan' => $detail->catatan,
                'current_stock' => $detail->sparepart->stok_sparepart,
                'new_stock_after_adjustment' => $detail->sparepart->stok_sparepart + $detail->selisih,
                'adjustment_history' => $detail->adjustments->map(function($adjustment) {
                    return [
                        'id' => $adjustment->id,
                        'stock_before' => $adjustment->stock_before,
                        'stock_after' => $adjustment->stock_after,
                        'adjustment_qty' => $adjustment->adjustment_qty,
                        'alasan_adjustment' => $adjustment->alasan_adjustment,
                        'created_at' => $adjustment->created_at->format('Y-m-d H:i:s'),
                        'user' => $adjustment->user ? $adjustment->user->name : null
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyimpan penyesuaian stok
    */
    public function saveAdjustment(Request $request, $periodId, $detailId)
    {
        $validator = Validator::make($request->all(), [
            'alasan_adjustment' => 'required|string',
            'adjustment_qty' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $user = $this->getThisUser();
            $kode_owner = $user->id_upline;

            // VALIDASI: Hanya admin yang bisa koreksi
            if ($user->jabatan != '1') {
                return response()->json([
                    'success' => false,
                    'message' => 'Hanya admin yang dapat melakukan koreksi stok'
                ], 403);
            }

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($periodId);

            $detail = StockOpnameDetail::with('sparepart.variants')
                ->where('period_id', $periodId)
                ->where('id', $detailId)
                ->first();

            if (!$detail) {
                return response()->json([
                    'success' => false,
                    'message' => 'Item tidak ditemukan dalam periode ini'
                ], 404);
            }

            // Verifikasi status - harus sudah adjusted
            if ($detail->status !== 'adjusted') {
                return response()->json([
                    'success' => false,
                    'message' => 'Item ini belum diperiksa atau tidak dapat disesuaikan'
                ], 400);
            }

            $sparepart = $detail->sparepart;
            $currentStock = $sparepart->stok_sparepart;
            $adjustmentQty = $request->adjustment_qty;
            $newStock = $currentStock + $adjustmentQty;

            // Validasi stok tidak boleh negatif
            if ($newStock < 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Stok tidak boleh menjadi negatif'
                ], 400);
            }

            // 1. Simpan riwayat penyesuaian
            $adjustment = StockOpnameAdjustment::create([
                'detail_id' => $detail->id,
                'stock_before' => $currentStock,
                'stock_after' => $newStock,
                'adjustment_qty' => $adjustmentQty,
                'alasan_adjustment' => $request->alasan_adjustment,
                'user_input' => $user->id,
                'kode_owner' => $kode_owner,
            ]);

            // 2. Update stok Sparepart
            $sparepart->stok_sparepart = $newStock;
            $sparepart->save();

            // 3. Update stok Product Variant
            $variant = $sparepart->variants->first();
            if ($variant) {
                $variant->stock = $newStock;
                $variant->save();
            }

            // 4. Update StockOpnameDetail
            $detail->stock_aktual = $newStock;
            $detail->selisih = $newStock - $detail->stock_tercatat;
            $detail->save();

            // 5. Catat di stock history
            if (class_exists('App\Models\StockHistory')) {
                StockHistory::create([
                    'sparepart_id' => $sparepart->id,
                    'quantity_change' => $adjustmentQty,
                    'reference_type' => 'stock_opname_correction',
                    'reference_id' => $period->kode_periode,
                    'stock_before' => $currentStock,
                    'stock_after' => $newStock,
                    'notes' => 'Koreksi stok oleh admin: ' . $request->alasan_adjustment,
                    'user_input' => $user->id,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Koreksi stok berhasil disimpan',
                'data' => [
                    'id' => $adjustment->id,
                    'detail_id' => $detail->id,
                    'stock_before' => $adjustment->stock_before,
                    'stock_after' => $adjustment->stock_after,
                    'adjustment_qty' => $adjustment->adjustment_qty,
                    'alasan_adjustment' => $adjustment->alasan_adjustment,
                    'created_at' => $adjustment->created_at->format('Y-m-d H:i:s'),
                    'sparepart' => [
                        'id' => $sparepart->id,
                        'kode_sparepart' => $sparepart->kode_sparepart,
                        'nama_sparepart' => $sparepart->nama_sparepart,
                        'stok_current' => $sparepart->stok_sparepart
                    ]
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error in saveAdjustment', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyelesaikan periode stock opname
     */
    public function completePeriod(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Verifikasi status
            if ($period->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah selesai'
                ], 400);
            }

            if ($period->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah dibatalkan'
                ], 400);
            }

            // Cek apakah masih ada item yang belum diperiksa
            $pendingCount = $period->details()->where('status', 'pending')->count();
            if ($pendingCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Masih ada {$pendingCount} item yang belum diperiksa"
                ], 400);
            }

            // Update status periode
            $period->status = 'completed';
            $period->save();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil diselesaikan',
                'data' => [
                    'id' => $period->id,
                    'status' => $period->status,
                    'status_text' => $period->status_text
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membatalkan periode stock opname
     */
    public function cancelPeriod(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Verifikasi status
            if ($period->status === 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah selesai dan tidak dapat dibatalkan'
                ], 400);
            }

            if ($period->status === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Stock opname ini sudah dibatalkan sebelumnya'
                ], 400);
            }

            // Verifikasi jika ada penyesuaian yang sudah dilakukan
            $adjustedCount = $period->details()->where('status', 'adjusted')->count();
            if ($adjustedCount > 0) {
                return response()->json([
                    'success' => false,
                    'message' => "Stock opname ini memiliki {$adjustedCount} item yang sudah disesuaikan dan tidak dapat dibatalkan"
                ], 400);
            }

            // Update status periode
            $period->status = 'cancelled';
            $period->save();

            return response()->json([
                'success' => true,
                'message' => 'Stock opname berhasil dibatalkan',
                'data' => [
                    'id' => $period->id,
                    'status' => $period->status,
                    'status_text' => $period->status_text
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan laporan stock opname
     */
    public function getReport(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            // Verifikasi status
            if ($period->status !== 'completed') {
                return response()->json([
                    'success' => false,
                    'message' => 'Laporan hanya dapat dilihat untuk stock opname yang sudah selesai'
                ], 400);
            }

            // Statistik
            $totalItems = $period->details->count();
            $itemsWithSelisih = $period->details->whereNotNull('selisih')->where('selisih', '!=', 0)->count();
            $positiveSelisih = $period->details->where('selisih', '>', 0)->sum('selisih');
            $negativeSelisih = $period->details->where('selisih', '<', 0)->sum('selisih');
            $totalAdjusted = $period->details->where('status', 'adjusted')->count();

            // Item dengan selisih terbesar (positif dan negatif)
            $largestPositive = $period->details->where('selisih', '>', 0)->sortByDesc('selisih')->take(5)->values();
            $largestNegative = $period->details->where('selisih', '<', 0)->sortBy('selisih')->take(5)->values();

            $result = [
                'period' => [
                    'id' => $period->id,
                    'kode_periode' => $period->kode_periode,
                    'nama_periode' => $period->nama_periode,
                    'tanggal_mulai' => $period->tanggal_mulai->format('Y-m-d'),
                    'tanggal_selesai' => $period->tanggal_selesai->format('Y-m-d'),
                    'status' => $period->status,
                    'status_text' => $period->status_text,
                    'catatan' => $period->catatan,
                    'created_at' => $period->created_at->format('Y-m-d H:i:s'),
                    'created_by' => $period->user ? $period->user->name : null
                ],
                'statistics' => [
                    'total_items' => $totalItems,
                    'items_with_selisih' => $itemsWithSelisih,
                    'positive_selisih' => $positiveSelisih,
                    'negative_selisih' => $negativeSelisih,
                    'total_adjusted' => $totalAdjusted,
                    'percentage_with_selisih' => $totalItems > 0 ? round(($itemsWithSelisih / $totalItems) * 100) : 0
                ],
                'largest_positive' => $largestPositive->map(function($item) {
                    return [
                        'id' => $item->id,
                        'kode_sparepart' => $item->sparepart->kode_sparepart,
                        'nama_sparepart' => $item->sparepart->nama_sparepart,
                        'stock_tercatat' => $item->stock_tercatat,
                        'stock_aktual' => $item->stock_aktual,
                        'selisih' => $item->selisih,
                        'status' => $item->status,
                        'status_text' => $item->status_text
                    ];
                }),
                'largest_negative' => $largestNegative->map(function($item) {
                    return [
                        'id' => $item->id,
                        'kode_sparepart' => $item->sparepart->kode_sparepart,
                        'nama_sparepart' => $item->sparepart->nama_sparepart,
                        'stock_tercatat' => $item->stock_tercatat,
                        'stock_aktual' => $item->stock_aktual,
                        'selisih' => $item->selisih,
                        'status' => $item->status,
                        'status_text' => $item->status_text
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan detail item dengan selisih
     */
    public function getItemsWithSelisih(Request $request, $id)
    {
        try {
            $kode_owner = $this->getThisUser()->id_upline;
            $perPage = $request->input('per_page', 10);

            $period = StockOpnamePeriod::where('kode_owner', $kode_owner)
                ->findOrFail($id);

            $itemsWithSelisih = $period->details()
                ->with('sparepart')
                ->whereNotNull('selisih')
                ->where('selisih', '!=', 0)
                ->orderBy(DB::raw('ABS(selisih)'), 'desc')
                ->paginate($perPage);

            $result = [
                'current_page' => $itemsWithSelisih->currentPage(),
                'last_page' => $itemsWithSelisih->lastPage(),
                'per_page' => $itemsWithSelisih->perPage(),
                'total' => $itemsWithSelisih->total(),
                'items' => $itemsWithSelisih->map(function($item) {
                    return [
                        'id' => $item->id,
                        'sparepart_id' => $item->sparepart_id,
                        'kode_sparepart' => $item->sparepart->kode_sparepart,
                        'nama_sparepart' => $item->sparepart->nama_sparepart,
                        'stock_tercatat' => $item->stock_tercatat,
                        'stock_aktual' => $item->stock_aktual,
                        'selisih' => $item->selisih,
                        'status' => $item->status,
                        'status_text' => $item->status_text
                    ];
                })
            ];

            return response()->json([
                'success' => true,
                'data' => $result
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ], 500);
        }
    }
}
