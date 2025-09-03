<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Penjualan;
use App\Models\Sparepart;
use App\Models\HargaKhusus;
use App\Models\DetailSparepartPenjualan;
use App\Traits\ManajemenKasTrait; // Tambahkan Trait
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Traits\KategoriLaciTrait;
use App\Models\SubKategoriSparepart;
use App\Models\KategoriSparepart;
use App\Models\PemasukkanLain;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;


class SalesApiController extends Controller
{
    use KategoriLaciTrait;
    use ManajemenKasTrait;
    public function getCategories()
    {
        try {
            $categories = KategoriSparepart::where('kode_owner', $this->getThisUser()->id_upline)
                ->with(['subKategoris' => function($query) {
                    $query->where('kode_owner', $this->getThisUser()->id_upline);
                }])
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Categories retrieved successfully',
                'data' => $categories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve categories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get subcategories by category ID
     */
    public function getSubCategories($categoryId)
    {
        try {
            $subCategories = SubKategoriSparepart::where('kategori_id', $categoryId)
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Subcategories retrieved successfully',
                'data' => $subCategories
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve subcategories',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function searchSuggestions(Request $request)
{
    try {
        $request->validate(['query' => 'required|string|min:2|max:255']);
        $originalQuery = trim($request->input('query'));
        $categoryId = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');

        $baseQuery = DB::table('spareparts')
            ->select([
                'spareparts.id',
                'spareparts.nama_sparepart',
                'spareparts.kode_sparepart',
                'spareparts.stok_sparepart',
                'spareparts.kode_kategori',
                'spareparts.kode_sub_kategori',
                'kategori_spareparts.nama_kategori',
                'sub_kategori_spareparts.nama_sub_kategori'
            ])
            ->leftJoin('kategori_spareparts', 'spareparts.kode_kategori', '=', 'kategori_spareparts.id')
            ->leftJoin('sub_kategori_spareparts', 'spareparts.kode_sub_kategori', '=', 'sub_kategori_spareparts.id')
            ->where('spareparts.kode_owner', $this->getThisUser()->id_upline)
            ->where('spareparts.stok_sparepart', '>', 0);

        // Filter kategori jika ada
        if ($categoryId) {
            $baseQuery->where('spareparts.kode_kategori', $categoryId);
        }
        if ($subcategoryId) {
            $baseQuery->where('spareparts.kode_sub_kategori', $subcategoryId);
        }

        $query = strtolower($originalQuery);
        $keywords = array_filter(array_map('trim', explode(' ', $query)));

        $suggestions = $baseQuery->where(function($q) use ($query, $keywords) {
            // Prioritas 1: Exact match di awal
            $q->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', $query . '%')
            ->orWhere(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', '% ' . $query . '%');

            // Prioritas 2: Exact match di kode sparepart
            $q->orWhere(DB::raw('LOWER(spareparts.kode_sparepart)'), 'LIKE', $query . '%');

            // Prioritas 3: Hanya jika lebih dari 1 keyword, cari semua keywords
            if (count($keywords) > 1) {
                $q->orWhere(function($subQ) use ($keywords) {
                    foreach ($keywords as $keyword) {
                        if (strlen($keyword) >= 2) {
                            $subQ->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                        }
                    }
                });
            }
        })
        ->orderByRaw("
            CASE
                WHEN LOWER(spareparts.nama_sparepart) LIKE '{$query}%' THEN 1
                WHEN LOWER(spareparts.nama_sparepart) LIKE '% {$query}%' THEN 2
                WHEN LOWER(spareparts.kode_sparepart) LIKE '{$query}%' THEN 3
                ELSE 4
            END, spareparts.nama_sparepart ASC
        ")
        ->limit(10)
        ->get();

        $filteredSuggestions = $suggestions->filter(function($item) use ($keywords, $query) {
            $itemName = strtolower($item->nama_sparepart);

            if (count($keywords) == 1) {
                $keyword = $keywords[0];
                return strpos($itemName, $keyword) !== false ||
                    strpos(strtolower($item->kode_sparepart), $keyword) !== false;
            }

            if (count($keywords) > 1) {
                $allKeywordsFound = true;
                foreach ($keywords as $keyword) {
                    if (strlen($keyword) >= 2 && strpos($itemName, $keyword) === false) {
                        $allKeywordsFound = false;
                        break;
                    }
                }

                if ($allKeywordsFound && preg_match('/\d+/', $query, $queryNumbers)) {
                    foreach ($queryNumbers as $number) {
                        if (strpos($itemName, $number) === false) {
                            $allKeywordsFound = false;
                            break;
                        }
                    }
                }

                return $allKeywordsFound;
            }

            return true;
        });

        return response()->json([
            'status' => 'success',
            'data' => $filteredSuggestions->values()->map(function($item) {
                return [
                    'id' => $item->id,
                    'nama_sparepart' => $item->nama_sparepart,
                    'kode_sparepart' => $item->kode_sparepart,
                    'stok_sparepart' => $item->stok_sparepart,
                    'kategori_nama' => $item->nama_kategori,
                    'sub_kategori_nama' => $item->nama_sub_kategori,
                    'display_text' => $item->nama_sparepart . ' (' . $item->kode_sparepart . ')'
                ];
            })
        ]);

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to get suggestions',
            'error' => $e->getMessage()
        ], 500);
    }
}


/**
 * Helper method untuk membangun kondisi semua keywords cocok
 */
private function buildAllKeywordsMatch($keywords)
{
    if (empty($keywords) || count($keywords) <= 1) {
        return "1=0"; // False condition
    }

    $conditions = [];
    foreach ($keywords as $keyword) {
        if (strlen($keyword) >= 2) {
            $conditions[] = "LOWER(nama_sparepart) LIKE '%{$keyword}%'";
        }
    }

    return empty($conditions) ? "1=0" : "(" . implode(" AND ", $conditions) . ")";
}

    /**
     * Pencarian detail sparepart
     * Dipanggil ketika user menekan tombol cari atau memilih dari suggestion
     */
    public function search(Request $request)
{
    try {
        $request->validate(['search' => 'required|string|max:255']);

        $searchInput = trim($request->input('search'));
        $categoryId = $request->input('category_id');
        $subcategoryId = $request->input('subcategory_id');
        $page = $request->input('page', 1);
        $limit = $request->input('limit', 20);
        $offset = ($page - 1) * $limit;

        $userId = $this->getThisUser()->id_upline;

        // Tentukan apakah pencarian bisa menggunakan cache
        $shouldCache = strlen($searchInput) < 10 && !preg_match('/\d/', $searchInput) && !$categoryId && !$subcategoryId;

        $cacheKey = 'search_detail_' . md5(json_encode($request->all()) . '_' . $userId);

        $searchFunction = function () use ($searchInput, $categoryId, $subcategoryId, $limit, $offset, $userId) {
            $cleanInput = preg_replace('/[^\w\s\/-]/', '', $searchInput);
            $keywords = array_filter(array_map('trim', explode(' ', strtolower($cleanInput))));
            $exactMatch = strtolower($cleanInput);

            // Query utama
           $query = DB::table('spareparts')
            ->leftJoin('harga_khususes', function ($join) {
                // Cukup join berdasarkan ID sparepart
                $join->on('spareparts.id', '=', 'harga_khususes.id_sp');
            })
            // Filter utama untuk data milik user sudah ada di sini, dan ini sudah cukup
            ->where('spareparts.kode_owner', $userId);

            if ($categoryId) {
                $query->where('spareparts.kode_kategori', $categoryId);
            }

            if ($subcategoryId) {
                $query->where('spareparts.kode_sub_kategori', $subcategoryId);
            }

            $query->where(function ($q) use ($keywords, $exactMatch) {
                $q->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', "%{$exactMatch}%")
                  ->orWhere(DB::raw('LOWER(spareparts.kode_sparepart)'), 'LIKE', "%{$exactMatch}%");

                if (count($keywords) > 1) {
                    $q->orWhere(function ($subQ) use ($keywords) {
                        foreach ($keywords as $keyword) {
                            if (strlen($keyword) > 2) {
                                $subQ->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', "%{$keyword}%");
                            }
                        }
                    });
                }
            });

            $totalCount = $query->count();

            $data = $query->select([
                'spareparts.id',
                'spareparts.kode_sparepart',
                'spareparts.nama_sparepart',
                'spareparts.kode_kategori',
                'spareparts.kode_sub_kategori',
                'spareparts.harga_beli',
                'spareparts.harga_jual',
                'spareparts.harga_ecer',
                'spareparts.harga_pasang',
                'spareparts.stok_sparepart',
                'spareparts.created_at',
                'spareparts.updated_at',
                'harga_khususes.harga_toko',
                'harga_khususes.harga_satuan'
            ])
            ->orderByRaw("
                CASE
                    WHEN LOWER(spareparts.nama_sparepart) LIKE '{$exactMatch}%' THEN 1
                    WHEN LOWER(spareparts.kode_sparepart) LIKE '{$exactMatch}%' THEN 2
                    WHEN LOWER(spareparts.nama_sparepart) LIKE '%{$exactMatch}%' THEN 3
                    ELSE 4
                END, spareparts.nama_sparepart ASC
            ")
            ->offset($offset)
            ->limit($limit)
            ->get();

            $enhancedData = $data->map(function ($item) {
                // Menambahkan properti baru dari data yang sudah ada
                $item->kategori_nama = optional(KategoriSparepart::find($item->kode_kategori))->nama_kategori;
                $item->sub_kategori_nama = optional(SubKategoriSparepart::find($item->kode_sub_kategori))->nama_sub_kategori;
                $item->stock_status = $item->stok_sparepart > 0 ? 'available' : 'out_of_stock';
                $item->low_stock = $item->stok_sparepart > 0 && $item->stok_sparepart <= 5;

                // 1. Buat object harga_khusus jika datanya ada
                if (!is_null($item->harga_toko) || !is_null($item->harga_satuan)) {
                    $item->harga_khusus = (object)[
                        'harga_toko' => $item->harga_toko,
                        'harga_satuan' => $item->harga_satuan,
                    ];

                    // 2. Timpa harga_jual / harga_pasang dari object baru
                    if (!is_null($item->harga_khusus->harga_toko)) {
                        $item->harga_jual = $item->harga_khusus->harga_toko;
                    }
                    if (!is_null($item->harga_khusus->harga_satuan)) {
                        $item->harga_pasang = $item->harga_khusus->harga_satuan;
                    }

                } else {
                    $item->harga_khusus = null;
                }

                // 3. Hapus properti lama dari level atas untuk kebersihan data
                unset($item->harga_toko);
                unset($item->harga_satuan);

                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'data' => $enhancedData,
                'pagination' => [
                    'current_page' => (int) ceil(($offset / $limit) + 1),
                    'total_items' => $totalCount,
                    'per_page' => $limit,
                    'total_pages' => (int) ceil($totalCount / $limit),
                    'has_more' => ($offset + $limit) < $totalCount
                ],
                'search_info' => [
                    'search_terms' => $keywords,
                    'original_query' => $searchInput
                ]
            ]);
        };

        return $shouldCache
            ? Cache::remember($cacheKey, 900, $searchFunction) // Cache selama 15 menit
            : $searchFunction();

    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Failed to retrieve data',
            'error' => $e->getMessage()
        ], 500);
    }
}


    /**
     * Mendapatkan sparepart berdasarkan ID spesifik
     * Untuk detail produk atau ketika memilih dari suggestion
     */
    public function getSparepartById($id)
    {
        try {
            $sparepart = DB::table('spareparts')
                ->where('id', $id)
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->select([
                    'id',
                    'kode_sparepart',
                    'nama_sparepart',
                    'kode_kategori',
                    'kode_sub_kategori',
                    'harga_beli',
                    'harga_jual',
                    'harga_ecer',
                    'harga_pasang',
                    'stok_sparepart',
                    'created_at',
                    'updated_at'
                ])
                ->first();

            if (!$sparepart) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Sparepart not found'
                ], 404);
            }

            // Enhance dengan data kategori
            $category = KategoriSparepart::find($sparepart->kode_kategori);
            $subcategory = SubKategoriSparepart::find($sparepart->kode_sub_kategori);

            $sparepart->kategori_nama = $category ? $category->nama_kategori : null;
            $sparepart->sub_kategori_nama = $subcategory ? $subcategory->nama_sub_kategori : null;
            $sparepart->stock_status = $sparepart->stok_sparepart > 0 ? 'available' : 'out_of_stock';
            $sparepart->low_stock = $sparepart->stok_sparepart > 0 && $sparepart->stok_sparepart <= 5;

            // Hitung harga berdasarkan customer type
            $customerTypes = ['ecer', 'glosir', 'jumbo'];
            $sparepart->price_options = [];

            foreach ($customerTypes as $type) {
                $sparepart->price_options[$type] = $this->calculatePriceForCustomerType($sparepart, $type);
            }

            return response()->json([
                'status' => 'success',
                'data' => $sparepart
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve sparepart',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan pencarian populer/trending
     */
    public function getPopularSearches()
    {
        try {
            // Ambil sparepart yang sering dicari/dibeli (berdasarkan penjualan)
            $popularItems = DB::table('detail_sparepart_penjualans as dsp')
                ->join('spareparts as sp', 'dsp.kode_sparepart', '=', 'sp.id')
                ->join('penjualans as p', 'dsp.kode_penjualan', '=', 'p.id')
                ->where('sp.kode_owner', $this->getThisUser()->id_upline)
                ->where('p.created_at', '>=', now()->subDays(30)) // 30 hari terakhir
                ->where('sp.stok_sparepart', '>', 0) // Yang masih ada stok
                ->select([
                    'sp.id',
                    'sp.nama_sparepart',
                    'sp.kode_sparepart',
                    DB::raw('COUNT(dsp.id) as total_sold'),
                    DB::raw('SUM(dsp.qty_sparepart) as total_qty')
                ])
                ->groupBy('sp.id', 'sp.nama_sparepart', 'sp.kode_sparepart')
                ->orderBy('total_sold', 'desc')
                ->limit(10)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $popularItems->map(function($item) {
                    return [
                        'id' => $item->id,
                        'nama_sparepart' => $item->nama_sparepart,
                        'kode_sparepart' => $item->kode_sparepart,
                        'display_text' => $item->nama_sparepart,
                        'badge' => 'Populer',
                        'sales_count' => $item->total_sold
                    ];
                })
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'success',
                'data' => []
            ]);
        }
    }

    public function cari(Request $request)
    {
        try {
            // Validasi input
            $request->validate(['search' => 'required|string|max:255']);



            // Ekstrak keywords
            $keywords = array_filter(explode(' ', strtolower(trim($request->input('search')))));
             $categoryId = $request->input('category_id');
            $subcategoryId = $request->input('subcategory_id');

            // Ambil owner_code dari request, dengan fallback ke user saat ini
            $ownerCode = $request->input('owner_code');



            // Buat query dasar
            $query = DB::table('spareparts')
                ->where('kode_owner', '=', $ownerCode);

                // Filter by category if provided
            if ($categoryId) {
                $query->where('kode_kategori', $categoryId);
            }

            // Filter by subcategory if provided
            if ($subcategoryId) {
                $query->where('kode_sub_kategori', $subcategoryId);
            }

            // Gunakan subquery untuk each keyword
            foreach ($keywords as $keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                });
            }

            // Pilih kolom yang dibutuhkan
            $data = $query->select([
                'id',
                'kode_sparepart',
                'kode_kategori',
                'kode_sub_kategori',
                'nama_sparepart',
                'stok_sparepart',
            ])->get();

            // Enhance data with category and subcategory names
            $enhancedData = $data->map(function($item) {
                $category = KategoriSparepart::find($item->kode_kategori);
                $subcategory = SubKategoriSparepart::find($item->kode_sub_kategori);

                $item->kategori_nama = $category ? $category->nama_kategori : null;
                $item->sub_kategori_nama = $subcategory ? $subcategory->nama_sub_kategori : null;

                return $item;
            });


            return response()->json([
                'status' => 'success',
                'message' => 'Data retrieved successfully',
                'total_items' => $data->count(),
                // 'data' => $data
                'data' => $enhancedData
            ], 200);
        } catch (\Exception $e) {
            // Log error detail
            // Log::error('Sparepart search error: ' . $e->getMessage());
            // Log::error($e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    // Get sales history
    public function getSalesHistory()
    {
        // Mendapatkan tanggal 7 hari terakhir
        $oneWeekAgo = now()->subDays(7)->toDateString();
        $today = now()->toDateString();

        $sales = Penjualan::where([
            ['kode_owner', '=', $this->getThisUser()->id_upline],
            ['status_penjualan', '!=', '0']
        ])
            ->whereBetween('tgl_penjualan', [$oneWeekAgo, $today]) // Filter 7 hari terakhir
            ->latest()
            ->with(['detailBarang', 'detailSparepart'])
            ->get();

        return response()->json([
            'status' => 'success',
            'data' => $sales
        ]);
    }

    // Perbaikan pada method createSale di SalesApiController.php
    public function createSale(Request $request)
    {
        $totalPenjualan = 0;
        $totalBayar = 0;

        // Cek apakah transaksi hanya disimpan sebagai draft (status 2)
        $statusPenjualan = ($request->simpan == 'simpan') ? '2' : '1';

        // Buat data penjualan terlebih dahulu
        $sale = Penjualan::create([
            'kode_penjualan' => 'TRX' . date('Ymd') . auth()->user()->id . (Penjualan::count() + 1),
            'tgl_penjualan' => date('Y-m-d'),
            'kode_owner' => $this->getThisUser()->id_upline,
            'nama_customer' => $request->nama_customer ?? '-',
            'catatan_customer' => $request->catatan_customer ?? '',
            'total_penjualan' => 0,
            'total_bayar' => 0,
            'user_input' => auth()->user()->id,
            'status_penjualan' => $statusPenjualan, // Bisa 1 (langsung bayar) atau 2 (draft)
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Array untuk melacak error stok
        $stockErrors = [];

        foreach ($request->items as $item) {
            $qtySparepart = $item['qty'];
            $sparepartId = $item['sparepart_id'];
            $customPrice = $item['custom_price'] ?? null; // Get custom price from request

            try {
                // Ambil data sparepart dari database
                $sparepart = Sparepart::findOrFail($sparepartId);

                // Sesuaikan harga jual berdasarkan tipe customer
                $calculatedPrice = $this->adjustPriceBasedOnCustomerType($sparepart, $item['customer_type'] ?? 'ecer');

                // Determine the final selling price
                $finalSellingPrice = $calculatedPrice; // Default to calculated price
                if ($customPrice !== null && is_numeric($customPrice)) {
                    // Use custom price if provided and it's not less than the original 'harga_ecer'
                    // Assuming 'harga_ecer' is the base counter price
                    if ($customPrice >= $sparepart->harga_ecer) {
                        $finalSellingPrice = (int) $customPrice;
                    } else {
                        // Optionally, add a warning or handle cases where customPrice is too low
                        // For now, it will just use the calculatedPrice if customPrice is less than harga_ecer
                        // You might want to log this or return a specific error
                    }
                }


                // PERBAIKAN: Periksa stok secara individu untuk masing-masing item
                if ($sparepart->stok_sparepart < $qtySparepart) {
                    $stockErrors[] = [
                        'id' => $sparepartId,
                        'name' => $sparepart->nama_sparepart,
                        'requested' => $qtySparepart,
                        'available' => $sparepart->stok_sparepart
                    ];
                    continue; // Lewati item ini tapi lanjutkan dengan yang lain
                }

                // Hitung total penjualan
                $totalPenjualan += $finalSellingPrice * $qtySparepart;
                $totalBayar += $finalSellingPrice * $qtySparepart;

                // Catat detail penjualan
                DetailSparepartPenjualan::create([
                    'kode_penjualan' => $sale->id,
                    'kode_sparepart' => $sparepartId,
                    'detail_harga_modal' => $sparepart->harga_beli,
                    'detail_harga_jual' => $finalSellingPrice, // Use the final selling price here
                    'qty_sparepart' => $qtySparepart,
                    'user_input' => auth()->user()->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                $stockErrors[] = [
                    'id' => $sparepartId,
                    'error' => $e->getMessage()
                ];
            }
        }

        // Update total penjualan
        $sale->update([
            'total_penjualan' => $totalPenjualan,
            'total_bayar' => ($statusPenjualan == '1') ? $totalBayar : 0, // Hanya update jika bukan draft
        ]);

        // Jika status bukan draft (status 1), maka tambahkan ke laci
        if ($statusPenjualan == '1') {
            $this->catatKas(
                $sale,
                $totalBayar,
                0,
                'Penjualan API #' . $sale->kode_penjualan,
                $sale->tgl_penjualan
            );
            $this->recordLaciHistory(
                $request->kategori_laci_id,
                $totalBayar, // Uang masuk
                null, // Tidak ada uang keluar
                'Penjualan: ' . $sale->kode_penjualan . '- customer: ' . ($request->nama_customer ?? '-')
            );
        }

        return response()->json([
            'status' => $stockErrors ? 'partial_success' : 'success',
            'message' => ($statusPenjualan == '2') ? 'Penjualan disimpan sebagai draft.' : 'Penjualan berhasil disimpan.',
            'data' => $sale,
            'stock_errors' => $stockErrors
        ]);

    }
    public function updateSaleStatus(Request $request)
    {
        $request->validate([
            'id_penjualan' => 'required|exists:penjualans,id',
        ]);

        // Cari data penjualan berdasarkan kode_penjualan
        $sale = Penjualan::where('id', $request->id_penjualan)->first();

        if (!$sale) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data penjualan tidak ditemukan',
            ], 404);
        }

        // Cek apakah status sudah lunas
        if ($sale->status_penjualan == '1') {
            return response()->json([
                'status' => 'error',
                'message' => 'Penjualan ini sudah lunas',
            ], 400);
        }

        // Update status menjadi lunas (1)
        $sale->update([
            'status_penjualan' => '1',
            'total_bayar' => $sale->total_penjualan, // Update total bayar
            'updated_at' => now(),
        ]);
        $this->catatKas(
            $sale,
            $sale->total_penjualan,
            0,
            'Pelunasan Penjualan #' . $sale->kode_penjualan,
            now() // Dicatat saat dilunasi
        );

        // Catat ke laci jika dibutuhkan
        $this->recordLaciHistory(
            $request->kategori_laci_id,
            $sale->total_penjualan, // Uang masuk
            null,
            'Pelunasan: ' . $sale->kode_penjualan . ' - Customer: ' . ($sale->nama_customer ?? '-')
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Status penjualan berhasil diperbarui menjadi lunas.',
            'data' => $sale,
        ]);
    }

    private function adjustPriceBasedOnCustomerType($sparepart, $selectedCustomerType)
    {
        $finalPrice = $sparepart->harga_ecer;

        if ($selectedCustomerType == 'ecer') {
            if ($finalPrice < 15000) {
                $finalPrice += $finalPrice * 0.1;
            } elseif ($finalPrice >= 15000 && $finalPrice <= 200000) {
                $finalPrice += 10000;
            } else {
                $finalPrice += 20000;
            }
        } elseif ($selectedCustomerType == 'glosir') {
            if ($finalPrice >= 5000 && $finalPrice < 15000) {
                $finalPrice -= 1000;
            } elseif ($finalPrice >= 50000 && $finalPrice < 200000) {
                $finalPrice -= 5000;
            }
        } elseif ($selectedCustomerType == 'jumbo') {
            if ($finalPrice >= 5000 && $finalPrice < 15000) {
                $finalPrice -= 2000;
            } elseif ($finalPrice >= 50000 && $finalPrice < 200000) {
                $finalPrice -= 10000;
            }
        }

        return $finalPrice;
    }

    // Get sale detail
    public function getSaleDetail($id)
    {
        $sale = Penjualan::with(['detailBarang', 'detailSparepart.sparepart:id,nama_sparepart'])
            ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

    // Update sale
    public function updateSale(Request $request, $id)
    {
        $sale = Penjualan::findOrFail($id);

        $updateData = [
            'tgl_penjualan' => $request->tgl_penjualan,
            'nama_customer' => $request->nama_customer ?? '-',
            'catatan_customer' => $request->catatan_customer ?? '-',
            'total_penjualan' => $request->total_penjualan,
            'total_bayar' => $request->total_bayar,
            'status_penjualan' => $request->status_penjualan,
            'updated_at' => Carbon::now(),
        ];

        if ($request->status_penjualan == '1' && $request->id_kategorilaci) {
            $this->recordLaciHistory(
                $request->id_kategorilaci,
                $request->total_penjualan,
                null,
                $request->nama_customer . "-" . $request->catatan_customer
            );
        }

        $sale->update($updateData);

        return response()->json([
            'status' => 'success',
            'data' => $sale
        ]);
    }

    public function createPemasukkanLainApi(Request $request)
    {
        // Validasi input request
        $request->validate([
            'jumlah_pemasukan' => ['required', 'numeric'],
        ]);
        try {
            // Buat record pemasukan baru
            $create = PemasukkanLain::create([
                'tgl_pemasukkan' => date('Y-m-d'),
                'judul_pemasukan' => $request->judul_pemasukan,
                'catatan_pemasukkan' => $request->catatan_pemasukan,
                'jumlah_pemasukkan' => $request->jumlah_pemasukan,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            // Jika pemasukan berhasil dibuat, catat histori laci
            if ($create) {
                $kategoriId = $request->id_kategorilaci;
                $uangMasuk = $request->input('jumlah_pemasukan');
                $keterangan = $request->input('judul_pemasukan') . "-" . $request->input('catatan_pemasukan');

                $this->recordLaciHistory($kategoriId, $uangMasuk, null, $keterangan);

                return response()->json([
                    'success' => true,
                    'message' => 'Pemasukkan berhasil ditambahkan',
                    'data' => $create,
                ], 201);
            }

            // Jika gagal, kirim response gagal
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan pemasukkan, ada kendala teknis',
            ], 500);
        } catch (\Exception $e) {
            // Handle exception dan kirim response error
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menambahkan pemasukkan',
                'error' => $e->getMessage(),
                'data' => $request->all(),
            ], 500);
        }
    }

    // Delete sale
    public function deleteSale($id)
    {
        $sale = Penjualan::findOrFail($id);
        $sale->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sale deleted successfully'
        ]);
    }

    // Tambahkan method baru ini di SalesApiController.php
    public function cancelSale(Request $request, $id)
    {
        // \Log::info('=== CANCEL SALE START ===');
        // \Log::info('Sale ID: ' . $id);
        // \Log::info('Request Data: ' . json_encode($request->all()));

        DB::beginTransaction();
        try {
            // Temukan penjualan
            // \Log::info('Finding sale with ID: ' . $id);
            $sale = Penjualan::findOrFail($id);
            // \Log::info('Sale found: ' . json_encode($sale->toArray()));

            // Hanya lanjutkan jika penjualan belum diproses/diselesaikan
            // \Log::info('Checking sale status: ' . $sale->status_penjualan);
            if ($sale->status_penjualan == '1') {
                // \Log::warning('Cannot cancel processed sale. Status: ' . $sale->status_penjualan);
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak dapat membatalkan penjualan yang sudah diproses'
                ], 400);
            }

            // Dapatkan semua detail penjualan
            // \Log::info('Getting sale details for sale ID: ' . $sale->id);
            $saleDetails = DetailSparepartPenjualan::where('kode_penjualan', $sale->id)->get();
            // \Log::info('Sale details count: ' . $saleDetails->count());
            // \Log::info('Sale details: ' . json_encode($saleDetails->toArray()));

            // Kembalikan stok untuk setiap item
            $stockUpdates = [];
            foreach ($saleDetails as $detail) {
                // \Log::info('Processing detail - Sparepart ID: ' . $detail->kode_sparepart . ', Qty: ' . $detail->qty_sparepart);

                $sparepart = Sparepart::find($detail->kode_sparepart);
                if ($sparepart) {
                    // \Log::info('Sparepart found: ' . json_encode($sparepart->toArray()));
                    // \Log::info('Current stock: ' . $sparepart->stok_sparepart);
                    // \Log::info('Returning qty: ' . $detail->qty_sparepart);

                    $oldStock = $sparepart->stok_sparepart;
                    $newStock = $oldStock + $detail->qty_sparepart;

                    // \Log::info('Stock calculation: ' . $oldStock . ' + ' . $detail->qty_sparepart . ' = ' . $newStock);

                    $updateResult = $sparepart->update([
                        'stok_sparepart' => $newStock
                    ]);

                    // \Log::info('Stock update result: ' . ($updateResult ? 'SUCCESS' : 'FAILED'));

                    // Verify update
                    $sparepart->refresh();
                    // \Log::info('Stock after update: ' . $sparepart->stok_sparepart);

                    $stockUpdates[] = [
                        'sparepart_id' => $detail->kode_sparepart,
                        'sparepart_name' => $sparepart->nama_sparepart,
                        'old_stock' => $oldStock,
                        'returned_qty' => $detail->qty_sparepart,
                        'new_stock' => $sparepart->stok_sparepart,
                        'update_success' => $updateResult
                    ];
                } else {
                    // \Log::error('Sparepart not found with ID: ' . $detail->kode_sparepart);
                    $stockUpdates[] = [
                        'sparepart_id' => $detail->kode_sparepart,
                        'error' => 'Sparepart not found'
                    ];
                }
            }

            // \Log::info('Stock updates summary: ' . json_encode($stockUpdates));

            // Hapus detail penjualan
            // \Log::info('Deleting sale details for sale ID: ' . $sale->id);
            $deletedDetailsCount = DetailSparepartPenjualan::where('kode_penjualan', $sale->id)->delete();
            // \Log::info('Deleted sale details count: ' . $deletedDetailsCount);

            // Hapus penjualan
            // \Log::info('Deleting sale with ID: ' . $sale->id);
            $saleDeleteResult = $sale->delete();
            // \Log::info('Sale deletion result: ' . ($saleDeleteResult ? 'SUCCESS' : 'FAILED'));

            DB::commit();
            // \Log::info('Transaction committed successfully');
            // \Log::info('=== CANCEL SALE SUCCESS ===');

            return response()->json([
                'status' => 'success',
                'message' => 'Penjualan berhasil dibatalkan dan stok dikembalikan',
                'debug_info' => [
                    'sale_id' => $id,
                    'details_processed' => $saleDetails->count(),
                    'stock_updates' => $stockUpdates,
                    'deleted_details_count' => $deletedDetailsCount
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            // \Log::error('=== CANCEL SALE ERROR ===');
            // \Log::error('Error type: ' . get_class($e));
            // \Log::error('Error message: ' . $e->getMessage());
            // \Log::error('Error file: ' . $e->getFile() . ':' . $e->getLine());
            // \Log::error('Stack trace: ' . $e->getTraceAsString());

            return response()->json([
                'status' => 'error',
                'message' => 'Gagal membatalkan penjualan: ' . $e->getMessage(),
                'debug_info' => [
                    'error_type' => get_class($e),
                    'error_file' => $e->getFile(),
                    'error_line' => $e->getLine()
                ]
            ], 500);
        }
    }
}
