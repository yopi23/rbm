<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\DetailPartServices;
use App\Models\Sparepart;
use App\Models\DetailPartLuarService;
use App\Models\Sevices as modelServices;
use App\Models\DetailCatatanService;
use App\Models\Garansi;
use App\Models\SalarySetting;
use App\Models\ProfitPresentase;
use App\Models\HargaKhusus;
use App\Traits\KategoriLaciTrait;
use App\Models\User; // Ensure User model is imported for teknisi name
use App\Models\UserDetail; // Ensure UserDetail model is imported for teknisi saldo/details
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SubKategoriSparepart;
use App\Models\KategoriSparepart;
use App\Models\Shift;
use Carbon\Carbon; // Added for date handling
use Illuminate\Validation\Rule;
use App\Traits\ManajemenKasTrait;


class SparepartApiController extends Controller
{
    use KategoriLaciTrait, ManajemenKasTrait;
    // Helper to get the authenticated user's detail.
    // Assuming this method exists in your base Controller or a trait.
    // If not, you'll need to define it or pass user details differently.
    public function getThisUser()
    {
        return auth()->user()->userDetail; // Example: assuming User has one-to-one UserDetail
    }

    /**
     * FUNGSI COMMAND/SEARCH LAMA - JANGAN DIHAPUS
     * Search sparepart dengan command system untuk Flutter
     */
    public function search_sparepart(Request $request)
    {
        try {
        $query = $request->input('query');
        $command = strtolower(trim($query));

        if (empty($command)) {
            return response()->json([
                'success' => true,
                'message' => "Apa yang Anda cari?\n\n" .
                    "Untuk mencari sparepart, ketik:\n" .
                    "part,nama sparepart toko\n\n" .
                    "Untuk menyelesaikan, ketik:\n" .
                    "selesaikan",
                'commands' => [
                    'part,(nama barang)',
                    'cancel',
                    'selesaikan',
                    'rincian atau rinci',
                    'wa (isi pesan)'
                ]
            ]);
        }

        // Cek apakah input dimulai dengan "part"
        if (str_starts_with($command, 'part')) {
            $pattern = '/^part[\s,:-]*(.+)$/';
            if (preg_match($pattern, $command, $matches)) {
                $searchQuery = trim($matches[1]);
                $keywords = array_filter(explode(' ', strtolower($searchQuery)));

                // Query dengan LEFT JOIN harga_khususes
                $queryBuilder = DB::table('spareparts')
                    ->leftJoin('harga_khususes', 'spareparts.id', '=', 'harga_khususes.id_sp')
                    ->where('spareparts.kode_owner', '=', $this->getThisUser()->id_upline)
                    ->where('spareparts.is_active', true); // Filter produk aktif

                // Filtering berdasarkan kata kunci
                foreach ($keywords as $keyword) {
                    $queryBuilder->where(function ($q) use ($keyword) {
                        $q->where(DB::raw('LOWER(spareparts.nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                    });
                }

                // Select kolom yang diperlukan
                $results = $queryBuilder->select([
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
                    'harga_khususes.harga_toko as harga_khusus_toko',
                    'harga_khususes.harga_satuan as harga_khusus_satuan',
                    'harga_khususes.diskon_tipe',
                    'harga_khususes.diskon_nilai',
                    'spareparts.created_at',
                    'spareparts.updated_at'
                ])->get();

                // Format data JSON
                $enhancedResults = $results->map(function ($item) {
                    $category = KategoriSparepart::find($item->kode_kategori);
                    $subcategory = SubKategoriSparepart::find($item->kode_sub_kategori);

                    $item->kategori_nama = $category ? $category->nama_kategori : null;
                    $item->sub_kategori_nama = $subcategory ? $subcategory->nama_sub_kategori : null;

                    // Format harga_khusus sebagai sub-objek
                    $item->harga_khusus = $item->harga_khusus_toko || $item->harga_khusus_satuan || $item->diskon_nilai ? [
                        'harga_toko' => $item->harga_khusus_toko,
                        'harga_satuan' => $item->harga_khusus_satuan,
                        'diskon_tipe' => $item->diskon_tipe,
                        'diskon_nilai' => $item->diskon_nilai
                    ] : null;

                    // Hapus kolom harga_khusus_* agar tidak duplikat
                    unset($item->harga_khusus_toko, $item->harga_khusus_satuan, $item->diskon_tipe, $item->diskon_nilai);

                    return $item;
                });

                return response()->json([
                    'success' => true,
                    'type' => 'sparepart_list',
                    'total_items' => $results->count(),
                    'data' => $enhancedResults
                ]);
            } else {
                return response()->json([
                    'success' => true,
                    'message' => "Format pencarian tidak valid. Gunakan format: 'part (nama barang)'"
                ]);
            }
        }


            // Cek apakah input dimulai dengan "wa"
            if (str_starts_with($command, 'wa')) {
                // Tangkap pesan setelah "wa"
                $pattern = '/^wa[\s,:-]*(.+)$/';
                if (preg_match($pattern, $command, $matches)) {
                    $pesan = trim($matches[1]);

                    if (empty($pesan)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Format perintah tidak valid. Gunakan: 'wa (pesan)'"
                        ]);
                    }

                    // Ambil service_id dari request
                    $id = $request->input('service_id');
                    $service = modelServices::find($id);

                    if (!$service || empty($service->no_telp)) {
                        return response()->json([
                            'success' => false,
                            'message' => "Nomor telepon tidak ditemukan untuk service ID $id"
                        ], 404);
                    }

                    $number = $service->no_telp;

                    // Kirim data ke API /send-message dengan token
                    $results =[
                        'number'  => $number,
                        'message' => $pesan,
                    ];

                    // Periksa respons dari API eksternal
                    if ($number) {
                        return response()->json([
                            'success' => true,
                            'type'    => 'chat',
                            'data' => $results,
                        ]);
                    } else {
                        return response()->json([
                            'success' => false,
                            'message' => "Gagal mengirim pesan",
                            'error'   => $response->json(),
                        ], 500);
                    }
                }
            }


            //end wa chat

            if ($command === 'selesaikan') {
                $jab = [1, 2]; // Jabatan yang harus dikecualikan
                $user = UserDetail::where('id_upline', $this->getThisUser()->id_upline)
                    ->whereNotIn('jabatan', $jab)
                    ->get();

                return response()->json([
                    'success' => true,
                    'type' => 'teknisi_list',
                    'message' => 'Pilih teknisi untuk melanjutkan.',
                    'data' => $user
                ]);
            }

            if ($command === 'rincian' || $command === 'rinci') {
                // Ambil service_id dari request
                $serviceId = $request->input('service_id');


                if ($serviceId) {
                    return response()->json([
                        'success' => true,
                        'type' => 'rincian',
                        'data' => $serviceId
                    ]);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Service tidak ditemukan',
                    ]);
                }
            }

            // Default response jika command tidak dikenali
            return response()->json([
                'success' => true,
                'message' => "Apa yang Anda cari?\n\n" .
                    "Untuk mencari sparepart, ketik:\n" .
                    "part,nama sparepart toko\n\n" .
                    "Untuk menyelesaikan, ketik:\n" .
                    "selesaikan",
                'commands' => [
                    'part,(nama barang)',
                    'cancel',
                    'selesaikan',
                    'rincian atau rinci',
                    'wa (isi pesan)'
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * FUNGSI DETAIL SERVICE LAMA - JANGAN DIHAPUS
     * Detail service dengan struktur data lengkap
     */
    public function detail_service($id)
    {
        try {
            $data = modelServices::findOrFail($id);

            $garansi = Garansi::where([
                ['type_garansi', '=', 'service'],
                ['kode_garansi', '=', $data->kode_service]
            ])->get();

            $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                ->where([['detail_catatan_services.kode_services', '=', $id]])
                ->get(['detail_catatan_services.id as id_catatan', 'detail_catatan_services.*', 'users.*']);

            $detail = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                ->where([['detail_part_services.kode_services', '=', $id]])
                ->get(['detail_part_services.id as id_detail_part', 'detail_part_services.*', 'spareparts.*']);

            $detail_luar = DetailPartLuarService::where([['kode_services', '=', $id]])->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'service' => $data,
                    'garansi' => $garansi,
                    'catatan' => $catatan,
                    'detail_part' => $detail,
                    'detail_part_luar' => $detail_luar
                ]
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get service details',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get details of a single service, including parts, warranty, and notes.
     * This can be used for both completed and uncompleted services, as it's a "read" operation.
     */
    public function getServiceDetails($id)
    {
        try {
            // Ambil data service berdasarkan ID
            $service = modelServices::findOrFail($id);

            // Ambil detail part toko dengan pengecekan jika data join tidak ditemukan
            $part_toko_service = DetailPartServices::leftJoin('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                ->where('detail_part_services.kode_services', $id)
                ->get([
                    'detail_part_services.id as detail_part_id',
                    'detail_part_services.qty_part',
                    'detail_part_services.detail_harga_part_service',
                    'spareparts.nama_sparepart',
                    'spareparts.kode_sparepart',
                    'spareparts.stok_sparepart', // Include current stock for display in UI
                    'spareparts.harga_jual', // Include harga_jual from spareparts table
                ])
                ->map(function ($item) {
                    return [
                        'detail_part_id' => $item->detail_part_id,
                        'qty_part' => $item->qty_part,
                        // Ensure 'harga_jual' is prioritized from the spareparts table for display consistency
                        'harga_jual' =>  $item->detail_harga_part_service ?? $item->harga_jual,
                        'nama_sparepart' => $item->nama_sparepart ?? 'Unknown Part',
                        'kode_sparepart' => $item->kode_sparepart ?? 'Unknown Code',
                        'stok_sparepart' => $item->stok_sparepart, // Pass through stock for UI
                    ];
                });

            // Ambil detail part luar toko
            $part_luar_toko_service = DetailPartLuarService::where('kode_services', $id)
                ->get([
                    'id as detail_part_luar_id',
                    'nama_part',
                    'qty_part',
                    'harga_part',
                ])
                ->map(function ($item) {
                    return [
                        'detail_part_luar_id' => $item->detail_part_luar_id,
                        'nama_part' => $item->nama_part ?? 'Unknown Part',
                        'qty_part' => $item->qty_part,
                        'harga_part' => $item->harga_part,
                    ];
                });

            // Gabungkan data menjadi format JSON yang rapi
            $details = [
                'service' => [
                    'id' => $service->id,
                    'kode_service' => $service->kode_service,
                    'nama_pelanggan' => $service->nama_pelanggan,
                    'no_telp' => $service->no_telp, // Added no_telp
                    'type_unit' => $service->type_unit,
                    'keterangan' => $service->keterangan, // Added keterangan
                    'status_services' => $service->status_services,
                    'total_biaya' => $service->total_biaya,
                    'dp' => $service->dp,
                    'harga_sp' => $service->harga_sp,
                    'claimed_from_service_id' => $service->claimed_from_service_id,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
                    // Add technician name if available
                    'teknisi' => $service->id_teknisi ? User::where('id', $service->id_teknisi)->value('name') : null,
                ],
                'part_toko' => $part_toko_service,
                'part_luar' => $part_luar_toko_service,
            ];

            return response()->json([
                'success' => true,
                'message' => 'Service details retrieved successfully.',
                'data' => $details,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve service details.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Search spareparts for adding to services (used in both contexts)
     */
    public function searchSparepartToko(Request $request)
    {
        try {
            $search = $request->input('search'); // Use 'search' as the query parameter name

            if (empty($search)) {
                return response()->json([
                    'success' => true,
                    'message' => 'Silakan masukkan kata kunci pencarian.',
                    'data' => [],
                    'pagination' => null
                ], 200);
            }

            // Split the search query into keywords
            $keywords = array_filter(explode(' ', strtolower($search)));

            $queryBuilder = Sparepart::where('kode_owner', $this->getThisUser()->id_upline);

            foreach ($keywords as $keyword) {
                $queryBuilder->where(function ($q) use ($keyword) {
                    $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                });
            }

            $results = $queryBuilder->select([
                'id',
                'kode_sparepart',
                'nama_sparepart',
                'kode_kategori',
                'kode_sub_kategori',
                'harga_beli',
                'harga_jual',
                'harga_ecer',
                'harga_pasang',
                'stok_sparepart', // Include stok_sparepart
                'created_at',
                'updated_at'
            ])->get();

            // Enhance data with category and subcategory names
            $enhancedResults = $results->map(function($item) {
                $category = KategoriSparepart::find($item->kode_kategori);
                $subcategory = SubKategoriSparepart::find($item->kode_sub_kategori);

                $item->kategori_nama = $category ? $category->nama_kategori : null;
                $item->sub_kategori_nama = $subcategory ? $subcategory->nama_sub_kategori : null;

                // Add stock status for easier client-side handling
                $item->stock_status = 'available';
                $item->low_stock = false;
                $stock = (int) $item->stok_sparepart; // Cast to int for comparison

                if ($stock <= 0) {
                    $item->stock_status = 'out_of_stock';
                } elseif ($stock <= 5) { // Example: low stock threshold
                    $item->low_stock = true;
                }

                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data sparepart ditemukan.',
                'data' => $enhancedResults,
                'pagination' => [ // Add pagination details
                    'current_page' => 1, // Assuming no actual pagination for search results in this specific endpoint
                    'total_items' => $enhancedResults->count(),
                    'per_page' => $enhancedResults->count(),
                    'total_pages' => 1,
                    'has_more' => false
                ],
                'search_info' => [
                    'search_terms' => $keywords,
                    'original_query' => $search
                ]
            ], 200);

        } catch (\Exception $e) {
            Log::error("Search Sparepart Toko Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mencari sparepart: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Update Service Details (can be used for both completed and uncompleted services)
     * This now triggers commission recalculation if total_biaya or dp changes and service is 'Selesai'.
     */
    // public function updateService(Request $request, $id)
    // {
    //     try {
    //         $validatedData = $request->validate([
    //             'nama_pelanggan' => 'nullable|string|max:255',
    //             'type_unit' => 'nullable|string|max:255',
    //             'keterangan' => 'nullable|string',
    //             'no_telp' => 'nullable|numeric',
    //             'total_biaya' => 'nullable|numeric|min:0',
    //             'dp' => 'nullable|numeric|min:0',
    //         ]);

    //         $service = ModelServices::findOrFail($id);

    //         // Store old values to check for changes
    //         $oldTotalBiaya = $service->total_biaya;
    //         $oldDp = $service->dp;

    //         $service->update($validatedData);

    //         // Trigger commission recalculation if relevant financial fields changed
    //         // AND the service is already marked as 'Selesai'
    //         if ($service->status_services === 'Selesai' && (
    //             (isset($validatedData['total_biaya']) && $validatedData['total_biaya'] != $oldTotalBiaya)
    //         )) {
    //             $this->performCommissionRecalculation($id);
    //         }

    //         return response()->json([
    //             'message' => 'Service updated successfully',
    //             'service' => $service,
    //         ], 200);
    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json([
    //             'message' => 'Service not found',
    //         ], 404);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Server error',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    // public function updateService(Request $request, $id)
    // {
    //     try {
    //         return \DB::transaction(function () use ($request, $id) {
    //             $service = ModelServices::findOrFail($id);

    //             // Simpan data lama
    //             $oldTotalBiaya = $service->total_biaya;
    //             $oldDp = $service->dp ?? 0;
    //             $oldLaciId = $service->id_kategorilaci;

    //             $validatedData = $request->validate([
    //                 'nama_pelanggan' => 'nullable|string|max:255',
    //                 'type_unit'      => 'nullable|string|max:255',
    //                 'keterangan'     => 'nullable|string',
    //                 'no_telp'        => 'nullable|numeric',
    //                 'total_biaya'    => 'nullable|numeric|min:0',
    //                 'dp'             => 'nullable|numeric|min:0',
    //                 'id_kategorilaci'=> [
    //                     'nullable',
    //                     'integer',
    //                     function ($attribute, $value, $fail) use ($request, $oldDp) {
    //                         $newDp = $request->input('dp', 0);

    //                         // Hanya wajib isi jika DP berubah
    //                         if ($newDp != $oldDp && $newDp > 0 && is_null($value)) {
    //                             $fail("Kolom $attribute wajib diisi jika DP berubah.");
    //                         }
    //                     }
    //                 ],
    //                 'tipe_sandi' => ['nullable', 'string', Rule::in(['pola', 'pin', 'teks'])],
    //                 'isi_sandi' => ['nullable', 'string', 'required_with:tipe_sandi'],
    //                 'data_unit' => ['nullable', 'json'],
    //             ]);

    //             // Ambil data baru dari input
    //             $newDp = $validatedData['dp'] ?? 0;
    //             $newLaciId = $validatedData['id_kategorilaci'] ?? null;

    //             // Update service dulu
    //             $service->update($validatedData);

    //             // Hitung selisih DP
    //             $dpDifference = $newDp - $oldDp;

    //             // ==== LOGIKA HISTORY LACI ====
    //             if ($dpDifference !== 0) {
    //                 if ($dpDifference > 0 && $newLaciId) {
    //                     // DP bertambah
    //                     $this->recordLaciHistory(
    //                         $newLaciId,
    //                         $dpDifference,
    //                         null,
    //                         "Update DP Service: {$service->kode_service} - a/n {$service->nama_pelanggan} (Penambahan)"
    //                     );
    //                 } elseif ($dpDifference < 0) {
    //                     if ($oldLaciId) {
    //                         // DP berkurang
    //                         $this->recordLaciHistory(
    //                             $oldLaciId,
    //                             null,
    //                             abs($dpDifference),
    //                             "Update DP Service: {$service->kode_service} - a/n {$service->nama_pelanggan} (Pengurangan)"
    //                         );
    //                     }

    //                     // Kalau pindah laci (baru dan lama beda)
    //                     if ($newDp > 0 && $newLaciId && $newLaciId != $oldLaciId) {
    //                         $this->recordLaciHistory(
    //                             $newLaciId,
    //                             $newDp,
    //                             null,
    //                             "Update DP Service: {$service->kode_service} - a/n {$service->nama_pelanggan} (Pindah Laci)"
    //                         );
    //                     }
    //                 }
    //             }
    //             // DP sama tapi pindah laci
    //             elseif ($oldLaciId != $newLaciId && $newDp > 0) {
    //                 if ($oldLaciId) {
    //                     $this->recordLaciHistory(
    //                         $oldLaciId,
    //                         null,
    //                         $newDp,
    //                         "Transfer DP Service: {$service->kode_service} - a/n {$service->nama_pelanggan} (Keluar)"
    //                     );
    //                 }
    //                 if ($newLaciId) {
    //                     $this->recordLaciHistory(
    //                         $newLaciId,
    //                         $newDp,
    //                         null,
    //                         "Transfer DP Service: {$service->kode_service} - a/n {$service->nama_pelanggan} (Masuk)"
    //                     );
    //                 }
    //             }

    //             // ==== Recalculate komisi kalau status sudah selesai/diambil & total biaya berubah ====
    //             $status = strtolower($service->status_services);

    //             if (in_array($status, ['selesai', 'diambil']) && $service->wasChanged('total_biaya')) {
    //                 $this->performCommissionRecalculation($id);
    //             }

    //             return response()->json([
    //                 'message' => 'Service updated successfully',
    //                 'service' => $service->fresh(),
    //             ], 200);
    //         });

    //     } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
    //         return response()->json([
    //             'message' => 'Service not found',
    //         ], 404);
    //     } catch (\Illuminate\Validation\ValidationException $e) {
    //         return response()->json([
    //             'message' => 'Validation failed',
    //             'errors' => $e->errors(),
    //         ], 422);
    //     } catch (\Exception $e) {
    //         return response()->json([
    //             'message' => 'Server error',
    //             'error' => $e->getMessage(),
    //         ], 500);
    //     }
    // }

    public function updateService(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            return \DB::transaction(function () use ($request, $id) {
                $service = modelServices::findOrFail($id);

                // 1. Cari riwayat DP lama di tabel history_laci berdasarkan kode_service
                // Kita cari transaksi 'masuk' yang pernah dicatat untuk service ini
                $oldHistory = \App\Models\HistoryLaci::where('reference_code', $service->kode_service)
                    ->where('reference_type', 'service')
                    ->where('masuk', '>', 0)
                    ->latest()
                    ->first();

                $oldDp = $service->dp ?? 0;
                $oldLaciId = $oldHistory ? $oldHistory->id_kategori : null;

                $validatedData = $request->validate([
                    'nama_pelanggan' => 'nullable|string|max:255',
                    'dp'             => 'nullable|numeric|min:0',
                    'id_kategorilaci'=> 'nullable|integer', // ID laci baru dari input user
                    'no_telp'        => 'nullable|numeric',
                    'total_biaya'    => 'nullable|numeric|min:0',
                    'type_unit'      => 'nullable|string|max:255',
                    'keterangan'     => 'nullable|string',
                ]);

                // 2. LOGIKA ROLLBACK (Penting agar tidak double)
                // Jika ditemukan ada riwayat uang masuk sebelumnya, kita tarik keluar dulu
                if ($oldHistory && $oldDp > 0) {
                    $tanggalAsli = $oldHistory->created_at->format('d/m/Y');
                    $this->recordLaciHistory(
                        $oldLaciId,
                        null,          // Masuk null
                        $oldDp,        // Keluar (sebesar DP lama)
                        "Rollback DP lama (Transaksi tgl $tanggalAsli) karena update: " . $service->nama_pelanggan . " - " . $service->kode_service . ' (' . $service->type_unit . ')',
                        'service',
                        $service->id,
                        $service->kode_service
                    );
                }

                // 3. Update data service di database
                $service->update($validatedData);

                // 3.1. Recalculate Commission if total_biaya changed and service is completed
                if ($request->has('total_biaya') && in_array(strtolower($service->status_services), ['selesai', 'diambil'])) {
                    $this->performCommissionRecalculation($service->id);
                }

                // 4. LOGIKA CATAT DATA BARU
                $newDp = $request->input('dp', 0);
                $newLaciId = $request->input('id_kategorilaci');

                // Jika ada nilai DP baru dan laci dipilih, catat sebagai uang masuk
                if ($newDp > 0 && $newLaciId) {
                    $this->recordLaciHistory(
                        $newLaciId,
                        $newDp,        // Masuk (sebesar DP baru)
                        null,          // Keluar null
                        "DP Service (Update): " . $service->nama_pelanggan . " - " . $service->kode_service . ' (' . $service->type_unit . ')',
                        'service',
                        $service->id,
                        $service->kode_service
                    );
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Data berhasil diupdate dan saldo laci disesuaikan'
                ]);
            });
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }


    /**
     * Helper function untuk mendapatkan saldo laci saat ini
     *
     * @param int $kategoriLaciId
     * @return float
     */
    private function getLaciBalance($kategoriLaciId)
    {
        try {
            $result = \DB::table('history_laci')
                ->where('id_kategorilaci', $kategoriLaciId)
                ->selectRaw('COALESCE(SUM(masuk), 0) - COALESCE(SUM(keluar), 0) as balance')
                ->first();

            return $result ? (float) $result->balance : 0;

        } catch (\Exception $e) {
            \Log::error('Error getting laci balance: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Deletes a service and all its related data (parts, notes, warranty, commission).
     * This should be a careful operation, suitable for both completed/uncompleted.
     */
    public function delete_service($id)
    {
        $service = modelServices::findOrFail($id);
        $serviceId = $service->id;

        DB::beginTransaction();
        try {
            // Delete related parts, notes, warranty
            DetailPartServices::where('kode_services', $serviceId)->delete();
            DetailPartLuarService::where('kode_services', $serviceId)->delete();
            DetailCatatanService::where('kode_services', $serviceId)->delete();
            Garansi::where('kode_garansi', $service->kode_service)->where('type_garansi', 'service')->delete();

            // Handle commission rollback if it exists
            $profitPresentase = ProfitPresentase::where('kode_service', $serviceId)->first();
            if ($profitPresentase) {
                $teknisi = UserDetail::where('kode_user', $profitPresentase->kode_user)->first();
                if ($teknisi) {
                    // Restore technician's balance by deducting the profit
                    $teknisi->update(['saldo' => $teknisi->saldo - $profitPresentase->profit]);
                }
                $profitPresentase->delete();
            }

            // Finally, delete the service
            $service->delete();

            DB::commit();

            return response()->json(['message' => 'Service and all related data deleted successfully.'], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting service {$id} and related data: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete service and related data.', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Recalculates commission for a given service.
     * This is a core helper method that should be called internally by other methods
     * whenever part data or total service cost changes for a 'Selesai' service.
     * It can also be exposed as an API endpoint for manual triggers.
     */
    public function recalculateCommission(Request $request, $serviceId) // Public method for API route
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        // Optional: Add authorization check here if this route should only be accessible by certain roles
        // For example: if ($this->getThisUser()->jabatan != 1) { /* return unauthorized */ }
        Log::info("API call to recalculateCommission for Service ID: $serviceId by User: " . auth()->user()->id);

        DB::beginTransaction();
        try {
            $commissionData = $this->performCommissionRecalculation($serviceId);
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commission recalculated and service updated successfully.',
                'data' => $commissionData,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Service or related data not found.',
                'error' => $e->getMessage()
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Recalculate Commission API Error for Service ID {$serviceId}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to recalculate commission.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal Server Error'
            ], 500);
        }
    }


    // private function performCommissionRecalculation($serviceId)
    // {
    //     Log::info("Internal: Starting performCommissionRecalculation for Service ID: $serviceId");

    //     $service = modelServices::findOrFail($serviceId);


    //     // Fetch all associated spare parts (toko and luar)
    //     $part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
    //         ->where('detail_part_services.kode_services', $serviceId)
    //         ->get(['detail_part_services.detail_harga_part_service', 'detail_part_services.qty_part', 'detail_part_services.harga_garansi']);

    //     $part_luar_toko_service = DetailPartLuarService::where('kode_services', $serviceId)->get(['harga_part', 'qty_part']);

    //     // Recalculate total_part (harga_sp)
    //     $total_part = 0;
    //     $total_garansi = 0;
    //     foreach ($part_toko_service as $part) {
    //         $total_part += $part->detail_harga_part_service * $part->qty_part;
    //         $total_garansi += $part->harga_garansi;
    //     }
    //     foreach ($part_luar_toko_service as $part) {
    //         $total_part += $part->harga_part * $part->qty_part;
    //     }

    //     // Update the service's harga_sp
    //     $service->update(['harga_sp' => $total_part]);
    //     Log::info("Internal: Service Harga SP updated to: $total_part for Service ID: $serviceId");

    //     if ($service->claimed_from_service_id !== null) {
    //         // --- LOGIKA UNTUK SERVICE KLAIM GARANSI ---
    //         Log::info("Internal: Service ID {$serviceId} is a warranty claim. Commission set to 0.");

    //         // Hapus komisi lama jika ada (untuk mengembalikan saldo teknisi)
    //         $oldProfit = ProfitPresentase::where('kode_service', $serviceId)->first();
    //         if($oldProfit) {
    //             $teknisi = UserDetail::where('kode_user', $oldProfit->kode_user)->first();
    //             if($teknisi) {
    //                 $teknisi->decrement('saldo', $oldProfit->profit);
    //             }
    //             $oldProfit->delete();
    //         }

    //         // Tidak ada komisi baru yang dihitung, cukup kembalikan status
    //         return [
    //             'service_id' => $service->id,
    //             'new_harga_sp' => $service->harga_sp,
    //             'new_profit' => 0,
    //             'total_garansi' => $total_garansi,
    //             'info' => 'Warranty claim, no commission awarded.'
    //         ];

    //     } else {
    //         // Recalculate profit ONLY if service is "Selesai" and a technician is assigned
    //         if (in_array(strtolower($service->status_services), ['selesai','diambil'])
    //             && $service->id_teknisi) {

    //             $serviceId = $service->id;
    //             $id_teknisi = $service->id_teknisi;

    //             DB::transaction(function () use ($service, $serviceId, $id_teknisi, $total_part) {
    //                 $presentaseSetting = SalarySetting::where('user_id', $id_teknisi)->first();
    //                 $teknisi = UserDetail::where('kode_user', $id_teknisi)->first();

    //                 if (!$teknisi || !$presentaseSetting) {
    //                     Log::warning("Internal: Technician or SalarySetting not found for ID: {$id_teknisi}. Commission not updated.");
    //                     return [
    //                         'service_id'   => $serviceId,
    //                         'new_harga_sp' => $service->harga_sp,
    //                         'new_profit'   => 0,
    //                         'warning'      => 'Technician or salary setting not found, commission skipped.'
    //                     ];
    //                 }

    //                 // Hapus profit lama dari saldo teknisi (jika ada)
    //                 $oldProfitPresentase = ProfitPresentase::where('kode_service', $serviceId)->first();
    //                 if ($oldProfitPresentase) {
    //                     $teknisi->decrement('saldo', $oldProfitPresentase->profit);
    //                     Log::info("Internal: Old profit deducted: {$oldProfitPresentase->profit} from Technician ID: {$id_teknisi}");
    //                 }

    //                 // Hitung ulang profit dari service
    //                 $total_service_profit = $service->total_biaya - ($total_part + $total_garansi);

    //                 $fix_profit_teknisi = 0;
    //                 $profit_untuk_toko = 0;

    //                 if ($presentaseSetting->compensation_type === 'percentage') {
    //                     if ($total_service_profit < 0) {
    //                         // Komisi negatif (rugi)
    //                         $fix_profit_teknisi = $total_service_profit * $presentaseSetting->max_percentage / 100;
    //                     } else {
    //                         // Untung
    //                         $fix_profit_teknisi = $total_service_profit * $presentaseSetting->percentage_value / 100;
    //                     }
    //                     $profit_untuk_toko = $total_service_profit - $fix_profit_teknisi;

    //                     Log::info("Internal (Percentage): New calculated profit: {$fix_profit_teknisi} for Technician ID: {$id_teknisi}");
    //                 } else {
    //                     // Gaji tetap
    //                     $fix_profit_teknisi = 0;
    //                     $profit_untuk_toko = $total_service_profit;

    //                     Log::info("Internal (Fixed): Profit generated for store: {$profit_untuk_toko} by Technician ID: {$id_teknisi}");
    //                 }

    //                 // Simpan atau update profit
    //                 $komisi = ProfitPresentase::updateOrCreate(
    //                     ['kode_service' => $serviceId],
    //                     [
    //                         'tgl_profit'      => now(),
    //                         'kode_presentase' => $presentaseSetting->id,
    //                         'kode_user'       => $id_teknisi,
    //                         'profit'          => $fix_profit_teknisi,
    //                         'profit_toko'     => $profit_untuk_toko,
    //                     ]
    //                 );

    //                 // Tambahkan profit baru (bisa positif atau negatif) ke saldo teknisi
    //                 $teknisi->increment('saldo', $fix_profit_teknisi);

    //                 // Update saldo final di record komisi
    //                 $komisi->update(['saldo' => $teknisi->fresh()->saldo]);

    //                 Log::info("Internal: Technician new saldo: {$teknisi->fresh()->saldo} for Technician ID: {$id_teknisi}");
    //             });
    //         }
    //     }
    //     Log::info("Internal: performCommissionRecalculation finished for Service ID: $serviceId");

    //     return [
    //         'service_id' => $service->id,
    //         'new_harga_sp' => $service->harga_sp,
    //         'new_profit' => isset($fix_profit_teknisi) ? $fix_profit_teknisi : 0,
    //     ];
    // }

    private function performCommissionRecalculation($serviceId)
    {
        Log::info("Internal: Starting performCommissionRecalculation for Service ID: $serviceId");

        $service = modelServices::findOrFail($serviceId);

        // Fetch all associated spare parts (toko and luar)
        $part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
            ->where('detail_part_services.kode_services', $serviceId)
            ->get(['detail_part_services.detail_harga_part_service', 'detail_part_services.qty_part', 'detail_part_services.harga_garansi']);

        $part_luar_toko_service = DetailPartLuarService::where('kode_services', $serviceId)->get(['harga_part', 'qty_part']);

        // Recalculate total_part (harga_sp) dan total_garansi
        $total_part = 0;
        $total_garansi = 0;

        foreach ($part_toko_service as $part) {
            $total_part += $part->detail_harga_part_service * $part->qty_part;
            // Tambahkan pengecekan null untuk harga_garansi
            $total_garansi += ($part->harga_garansi ?? 0);
        }

        foreach ($part_luar_toko_service as $part) {
            $total_part += $part->harga_part * $part->qty_part;
        }

        // Update the service's harga_sp
        $service->update(['harga_sp' => $total_part]);
        Log::info("Internal: Service Harga SP updated to: $total_part, Total Garansi: $total_garansi for Service ID: $serviceId");

        // Initialize variables yang akan di-return
        $fix_profit_teknisi = 0;
        $profit_untuk_toko = 0;

        if ($service->claimed_from_service_id !== null) {
            // --- LOGIKA UNTUK SERVICE KLAIM GARANSI ---
            Log::info("Internal: Service ID {$serviceId} is a warranty claim. Commission set to 0.");

            // Hapus komisi lama jika ada (untuk mengembalikan saldo teknisi)
            $records = ProfitPresentase::where('kode_service', $serviceId)->orderBy('id')->get();
            if ($records->count() > 0) {
                $totalOld = $records->sum('profit');
                $primary = $records->first();
                $teknisi = $primary ? UserDetail::where('kode_user', $primary->kode_user)->first() : null;
                if ($teknisi && $totalOld != 0) {
                    $teknisi->decrement('saldo', $totalOld);
                }
                if ($primary) {
                    $primary->update([
                        'profit' => 0,
                        'profit_toko' => 0,
                        'saldo' => $teknisi ? $teknisi->fresh()->saldo : $primary->saldo,
                        'tgl_profit' => now(),
                    ]);
                    ProfitPresentase::where('kode_service', $serviceId)->where('id', '!=', $primary->id)->delete();
                }
            }

            return [
                'service_id' => $service->id,
                'new_harga_sp' => $service->harga_sp,
                'new_profit' => 0,
                'total_garansi' => $total_garansi,
                'info' => 'Warranty claim, no commission awarded.'
            ];

        } else {
            // Recalculate profit ONLY if service is "Selesai" or "Diambil" and a technician is assigned
            if (in_array(strtolower($service->status_services), ['selesai','diambil']) && $service->id_teknisi) {

                $serviceId = $service->id;
                $id_teknisi = $service->id_teknisi;

                DB::transaction(function () use ($service, $serviceId, $id_teknisi, $total_part, $total_garansi, &$fix_profit_teknisi, &$profit_untuk_toko) {
                    $presentaseSetting = SalarySetting::where('user_id', $id_teknisi)->first();
                    $teknisi = UserDetail::where('kode_user', $id_teknisi)->first();

                    if (!$teknisi || !$presentaseSetting) {
                        Log::warning("Internal: Technician or SalarySetting not found for ID: {$id_teknisi}. Commission not updated.");
                        return;
                    }

                    // Hapus profit lama dari saldo teknisi (jika ada)
                    $oldProfitRecords = ProfitPresentase::where('kode_service', $serviceId)->orderBy('id')->get();
                    $totalOld = $oldProfitRecords->sum('profit');
                    if ($totalOld != 0) {
                        $teknisi->decrement('saldo', $totalOld);
                        Log::info("Internal: Old profits deducted total: {$totalOld} from Technician ID: {$id_teknisi}");
                    }

                    // Hitung ulang profit dari service (DENGAN GARANSI)
                    $total_service_profit = $service->total_biaya - ($total_part + $total_garansi);

                    Log::info("Internal: Profit calculation - Total Biaya: {$service->total_biaya}, Total Part: {$total_part}, Total Garansi: {$total_garansi}, Service Profit: {$total_service_profit}");

                    if ($presentaseSetting->compensation_type === 'percentage') {
                        if ($total_service_profit < 0) {
                            // Komisi negatif (rugi)
                            $fix_profit_teknisi = $total_service_profit * $presentaseSetting->max_percentage / 100;
                        } else {
                            // Untung
                            $fix_profit_teknisi = $total_service_profit * $presentaseSetting->percentage_value / 100;
                        }
                        $profit_untuk_toko = $total_service_profit - $fix_profit_teknisi;

                        Log::info("Internal (Percentage): New calculated profit: {$fix_profit_teknisi} for Technician ID: {$id_teknisi}");
                    } else {
                        // Gaji tetap
                        $fix_profit_teknisi = 0;
                        $profit_untuk_toko = $total_service_profit;

                        Log::info("Internal (Fixed): Profit generated for store: {$profit_untuk_toko} by Technician ID: {$id_teknisi}");
                    }

                        $komisi = null;
                        if ($oldProfitRecords->isEmpty()) {
                            $komisi = ProfitPresentase::create([
                                'kode_service'    => $serviceId,
                                'tgl_profit'      => now(),
                                'kode_presentase' => $presentaseSetting->id,
                                'kode_user'       => $id_teknisi,
                                'profit'          => $fix_profit_teknisi,
                                'profit_toko'     => $profit_untuk_toko,
                            ]);
                        } else {
                            $primary = $oldProfitRecords->first();
                            $primary->update([
                                'tgl_profit'      => now(),
                                'kode_presentase' => $presentaseSetting->id,
                                'kode_user'       => $id_teknisi,
                                'profit'          => $fix_profit_teknisi,
                                'profit_toko'     => $profit_untuk_toko,
                            ]);
                            ProfitPresentase::where('kode_service', $serviceId)->where('id', '!=', $primary->id)->delete();
                            $komisi = $primary;
                        }

                    // Tambahkan profit baru (bisa positif atau negatif) ke saldo teknisi
                    $teknisi->increment('saldo', $fix_profit_teknisi);

                    // Update saldo final di record komisi
                    $komisi->update(['saldo' => $teknisi->fresh()->saldo]);

                    Log::info("Internal: Technician new saldo: {$teknisi->fresh()->saldo} for Technician ID: {$id_teknisi}");
                });
            }
        }

        Log::info("Internal: performCommissionRecalculation finished for Service ID: $serviceId");

        return [
            'service_id' => $service->id,
            'new_harga_sp' => $service->harga_sp,
            'new_profit' => $fix_profit_teknisi,
            'profit_toko' => $profit_untuk_toko,
            'total_garansi' => $total_garansi,
        ];
    }

    /**
     * Functions for COMPLETED Services - Sparepart Toko (Store Parts)
     * These specifically handle adding/updating/deleting store parts for services
     * that are already in a 'Selesai' status and correctly recalculate commission.
     */

    // NEW: Handles adding/updating store parts for completed services, ensures stock management and recalculates commission.
    // This is distinct from a generic 'storeSparepartToko' that might not always trigger commission recalculation.
    // Name this something like 'addOrUpdatePartTokoForCompletedService' for clarity, but sticking to your 'restoreSparepartTokoClean' mapping.
    public function addPartTokoToCompletedService(Request $request) // Renamed from restoreSparepartTokoClean for clarity on its purpose
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'kode_services' => 'required|exists:sevices,id', // Ensure service exists
                'kode_sparepart' => 'required|exists:spareparts,id', // Ensure sparepart exists
                'qty_part' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $serviceId = $request->kode_services;
            $sparepartId = $request->kode_sparepart;
            $qtyToAdd = $request->qty_part;

            DB::beginTransaction();
            try {
                $service = modelServices::findOrFail($serviceId);
                // Ensure the service is in a state where parts can be modified and commission recalculated
                // This check is implied if this function is strictly for "completed" services.
                // If it can be used for any status, adjust accordingly.

                $sparepart = Sparepart::findOrFail($sparepartId);

                $existingPart = DetailPartServices::where('kode_services', $serviceId)
                    ->where('kode_sparepart', $sparepartId)
                    ->first();

                $currentStockInDb = (int) $sparepart->stok_sparepart; // Ensure it's an integer

                if ($existingPart) {
                    $oldQtyForService = (int) $existingPart->qty_part;
                    $newTotalQtyForService = $oldQtyForService + $qtyToAdd;

                    // Calculate the stock difference: current stock - (new total quantity for service - old quantity for service)
                    // This is equivalent to: current_stock - qty_to_add
                    $projectedNewOverallStock = $currentStockInDb - $qtyToAdd;

                    if ($projectedNewOverallStock < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal ditambahkan. Stok sparepart tidak cukup. Stok saat ini: ' . $currentStockInDb
                        ], 400);
                    }

                    $existingPart->update([
                        'qty_part' => $newTotalQtyForService,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);

                } else {
                    // New part for this service
                    $projectedNewOverallStock = $currentStockInDb - $qtyToAdd;

                    if ($projectedNewOverallStock < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Stock tidak cukup: ' . $currentStockInDb
                        ], 400);
                    }

                    DetailPartServices::create([
                        'kode_services' => $serviceId,
                        'kode_sparepart' => $sparepartId,
                        'detail_modal_part_service' => $sparepart->harga_beli,
                        'detail_harga_part_service' => $sparepart->harga_jual,
                        'qty_part' => $qtyToAdd,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);
                }

                // Recalculate commission
                $commissionData = $this->performCommissionRecalculation($serviceId);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sparepart toko berhasil ditambahkan/diupdate dan komisi dihitung ulang.',
                    'data' => [
                        'service_id' => $serviceId,
                        'sparepart_id' => $sparepartId,
                        'qty_added' => $qtyToAdd,
                        'remaining_stock' => $projectedNewOverallStock,
                        'commission_data' => $commissionData
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Add/Update Part Toko for Completed Service Error: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi gagal: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // NEW: Updates the quantity of an existing store part for a service.
    // This addresses the Flutter `_updateSparepartTokoQty` call.
    public function updatePartTokoQuantityForCompletedService(Request $request, $detailPartId) // Renamed from updateSparepartToko
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'qty_part' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            try {
                $detailPartService = DetailPartServices::findOrFail($detailPartId);
                $serviceId = $detailPartService->kode_services;

                $oldQty = (int) $detailPartService->qty_part;
                $newQty = (int) $request->qty_part;

                $sparepart = Sparepart::findOrFail($detailPartService->kode_sparepart);
                $currentStockInDb = (int) $sparepart->stok_sparepart;

                // Calculate stock change required: (old_qty - new_qty)
                // If newQty > oldQty, stockChange is negative (meaning we need to deduct more)
                // If newQty < oldQty, stockChange is positive (meaning we return some stock)
                $stockAdjustment = $oldQty - $newQty;
                $projectedNewOverallStock = $currentStockInDb + $stockAdjustment;

                if ($projectedNewOverallStock < 0) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => 'Stock tidak cukup untuk menyesuaikan jumlah sparepart. Stok saat ini: ' . $currentStockInDb
                    ], 400);
                }

                $detailPartService->update([
                    'qty_part' => $newQty,
                    'user_input' => auth()->user()->id,
                ]);

                $sparepart->update([
                    'stok_sparepart' => $projectedNewOverallStock,
                ]);

                // Recalculate commission
                $commissionData = $this->performCommissionRecalculation($serviceId);

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Jumlah sparepart toko berhasil diubah dan komisi dihitung ulang.',
                    'data' => [
                        'detail_part_id' => $detailPartId,
                        'service_id' => $serviceId,
                        'new_qty' => $newQty,
                        'remaining_stock' => $projectedNewOverallStock,
                        'commission_data' => $commissionData
                    ]
                ], 200);

            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Detail sparepart tidak ditemukan.',
                ], 404);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Update Part Toko Quantity Error for ID {$detailPartId}: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Gagal mengubah jumlah sparepart toko dan menghitung ulang komisi.',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // NEW: Deletes a store part from a service.
    public function deletePartTokoFromCompletedService($detailPartId) // Renamed from deleteSparepartTokoClean
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $data = DetailPartServices::findOrFail($detailPartId);

            $serviceId = $data->kode_services;
            $sparepartId = $data->kode_sparepart;
            $deletedQty = (int) $data->qty_part; // Ensure it's an integer

            $sparepart = Sparepart::findOrFail($sparepartId);
            $stok_baru = (int) $sparepart->stok_sparepart + $deletedQty;

            $variant = $data->variant;

            $sparepart->update(['stok_sparepart' => $stok_baru]);
            if ($variant) {
                $variant->increment('stock', $detailPart->qty_part);
            }

            $data->delete();

            $commissionData = $this->performCommissionRecalculation($serviceId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart toko berhasil dihapus dan komisi dihitung ulang.',
                'data' => [
                    'deleted_detail_part_id' => $detailPartId,
                    'service_id' => $serviceId,
                    'sparepart_id' => $sparepartId,
                    'restored_qty_to_stock' => $deletedQty,
                    'remaining_stock' => $stok_baru,
                    'commission_data' => $commissionData
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Detail sparepart toko tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Delete Part Toko From Completed Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus sparepart toko dan menghitung ulang komisi.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Functions for COMPLETED Services - Sparepart Luar (External Parts)
     * These specifically handle adding/updating/deleting external parts for services
     * that are already in a 'Selesai' status and correctly recalculate commission.
     */

    // NEW: Adds an external part to a service.
    public function addPartLuarToCompletedService(Request $request) // Renamed from storeSparepartLuar
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'kode_services' => 'required|exists:sevices,id',
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $serviceId = $request->kode_services;
            // PERUBAHAN: Ambil model service untuk digunakan di trait kas
            $service = modelServices::findOrFail($serviceId);

            $create = DetailPartLuarService::create([
                'kode_services' => $serviceId,
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            // PERUBAHAN: Hitung total biaya dan catat sebagai kas keluar
            $totalCost = $request->harga_part * $request->qty_part;
            $deskripsi = "Biaya Part Luar: {$request->nama_part} (x{$request->qty_part}) untuk Service {$service->kode_service}";
            $this->catatKas($service, 0, $totalCost, $deskripsi);

            // Lanjutkan dengan kalkulasi komisi
            $this->performCommissionRecalculation($serviceId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart luar berhasil ditambahkan dan komisi dihitung ulang.',
                'data' => $create
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Add Part Luar to Completed Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // NEW: Updates an existing external part for a service.
    public function updatePartLuarForCompletedService(Request $request, $detailPartLuarId) // Renamed from updateSparepartLuar
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        try {
            $update = DetailPartLuarService::findOrFail($detailPartLuarId);
            $serviceId = $update->kode_services;
            $service = modelServices::findOrFail($serviceId);

            // PERUBAHAN: Hitung biaya lama sebelum update
            $oldCost = $update->harga_part * $update->qty_part;

            $update->update([
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            // PERUBAHAN: Hitung selisih biaya dan catat ke kas
            $newCost = $request->harga_part * $request->qty_part;
            $costDifference = $newCost - $oldCost;
            $deskripsi = "Update Biaya Part Luar: {$request->nama_part} untuk Service {$service->kode_service}";

            if ($costDifference > 0) {
                $this->catatKas($service, 0, $costDifference, $deskripsi);
            } elseif ($costDifference < 0) {
                $this->catatKas($service, abs($costDifference), 0, $deskripsi);
            }

            $this->performCommissionRecalculation($serviceId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart luar berhasil diupdate dan komisi dihitung ulang.',
                'data' => $update
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Detail sparepart luar tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Update Part Luar for Completed Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // NEW: Deletes an external part from a service.
    public function deletePartLuarFromCompletedService($detailPartLuarId) // Renamed from deleteSparepartLuar
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $data = DetailPartLuarService::findOrFail($detailPartLuarId);
            $serviceId = $data->kode_services;
            // PERUBAHAN: Ambil model service untuk digunakan di trait kas
            $service = modelServices::findOrFail($serviceId);

            // PERUBAHAN: Hitung total biaya yang dikembalikan dan buat deskripsi
            $totalCostReversed = $data->harga_part * $data->qty_part;
            $deskripsi = "Koreksi/Hapus Part Luar: {$data->nama_part} (x{$data->qty_part}) untuk Service {$service->kode_service}";

            // Hapus data part luar
            $data->delete();

            // PERUBAHAN: Catat pengembalian sebagai kas masuk
            $this->catatKas($service, $totalCostReversed, 0, $deskripsi);

            // Lanjutkan dengan kalkulasi komisi
            $this->performCommissionRecalculation($serviceId);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Sparepart luar berhasil dihapus dan komisi dihitung ulang.'
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Detail sparepart luar tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Delete Part Luar from Completed Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Functions for UNCOMPLETED Services (e.g., 'Antri', 'Proses') - Sparepart Toko
     * These should typically NOT trigger commission recalculation unless the service
     * status transitions to 'Selesai' (which would be handled by updateServiceStatus).
     * These are for initial part assignment or adjustments while service is in progress.
     */

    // Generic function to add/update store parts (for uncompleted or initial assignment)
    public function storeSparepartToko(Request $request)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'kode_services' => 'required|exists:sevices,id',
                'kode_sparepart' => 'required|exists:spareparts,id',
                'qty_part' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sparepart = \App\Models\Sparepart::find($request->kode_sparepart);
            if (!$sparepart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sparepart not found'
                ], 404);
            }

            DB::beginTransaction();
            try {
                // Ambil data service untuk cek status klaim
                $service = \App\Models\Sevices::findOrFail($request->kode_services);
                $isWarrantyClaim = $service->claimed_from_service_id !== null;

                $cek = \App\Models\DetailPartServices::where([
                    ['kode_services', '=', $request->kode_services],
                    ['kode_sparepart', '=', $request->kode_sparepart]
                ])->first();

                $currentStockInDb = (int) $sparepart->stok_sparepart;

                // ============================================================
                // PERSIAPAN DATA KEUANGAN
                // ============================================================
                $hargaPartService = 0;
                $isHargaKhusus = false;
                // Hitung biaya modal SEKARANG karena akan digunakan di bawah
                $biayaModalPart = $sparepart->harga_beli * $request->qty_part;

                // ============================================================
                // LOGIKA HARGA FINAL (DENGAN PENGECEKAN KLAIM GARANSI)
                // ============================================================
                if ($isWarrantyClaim) {
                    // Jika ini service klaim garansi, harga jual untuk pelanggan adalah 0.
                    $hargaPartService = 0;
                    Log::info("Warranty claim service: setting part price to 0 for service ID {$service->id}");
                } else {
                    // Jika BUKAN service klaim, jalankan logika harga normal
                    $hargaKhusus = DB::table('harga_khususes')
                        ->where('id_sp', $request->kode_sparepart)
                        ->first();

                    // 1. Siapkan harga default
                    $hargaPartService = $sparepart->harga_jual;

                    // 2. Timpa dengan harga khusus jika ada
                    if ($hargaKhusus && isset($hargaKhusus->harga_toko) && $hargaKhusus->harga_toko > 0) {
                        $hargaPartService = $hargaKhusus->harga_toko;
                        $isHargaKhusus = true;
                    }
                }

                if ($cek) { // Jika sparepart sudah pernah ditambahkan ke service ini
                    $oldQtyForService = (int) $cek->qty_part;
                    $newTotalQtyForService = $oldQtyForService + $request->qty_part;
                    $projectedNewOverallStock = $currentStockInDb - $request->qty_part;

                    if ($projectedNewOverallStock < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal ditambahkan karena Stok tidak cukup: ' . $currentStockInDb
                        ], 400);
                    }

                    $cek->update([
                        'qty_part' => $newTotalQtyForService,
                        'detail_harga_part_service' => $hargaPartService,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);

                } else { // Jika sparepart baru ditambahkan ke service ini
                    $projectedNewOverallStock = $currentStockInDb - $request->qty_part;

                    if ($projectedNewOverallStock < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Stok tidak cukup: ' . $currentStockInDb
                        ], 400);
                    }

                    \App\Models\DetailPartServices::create([
                        'kode_services' => $request->kode_services,
                        'kode_sparepart' => $request->kode_sparepart,
                        'detail_modal_part_service' => $sparepart->harga_beli,
                        'detail_harga_part_service' => $hargaPartService,
                        'qty_part' => $request->qty_part,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);
                }

                // PANGGIL TRAIT UNTUK MENCATAT KERUGIAN KE BUKU BESAR (JIKA KLAIM)
                if ($isWarrantyClaim) {
                    $deskripsiKerugian = "Biaya Garansi: Part {$sparepart->nama_sparepart} (x{$request->qty_part}) untuk Service {$service->kode_service}";

                    $this->catatKas(
                        $service,
                        0,
                        $biayaModalPart,
                        $deskripsiKerugian
                    );

                    Log::info("Warranty cost recorded to ledger: {$biayaModalPart} for service ID {$service->id}");
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi sparepart berhasil',
                    'data' => [
                        'kode_services' => $request->kode_services,
                        'kode_sparepart' => $request->kode_sparepart,
                        'qty' => $request->qty_part,
                        'remaining_stock' => $sparepart->fresh()->stok_sparepart,
                        'harga_used' => $hargaPartService,
                        'is_harga_khusus' => $isHargaKhusus,
                        'is_warranty_claim' => $isWarrantyClaim
                    ]
                ], 200);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Store Sparepart Toko Error: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi gagal: ' . $e->getMessage(),
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }


    // Generic function to delete store parts (for uncompleted or general use)
    public function deletePartTokoFromService($detailPartId)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $detailPart = DetailPartServices::findOrFail($detailPartId);

            // Dapatkan data terkait (masih perlu untuk nama part dan update stok)
            $service = $detailPart->service;
            $sparepart = $detailPart->sparepart;
            $variant = $detailPart->variant;

            // =========================================================================
            // PERBAIKAN KUNCI: Mengambil modal dari snapshot saat transaksi terjadi
            // Ini memastikan akurasi jika harga di tabel master sparepart berubah.
            $modalPartYangDihapus = $detailPart->detail_modal_part_service * $detailPart->qty_part;
            // =========================================================================

            // 2. Kembalikan stok sparepart
            $sparepart->increment('stok_sparepart', $detailPart->qty_part);
            if ($variant) {
                $variant->increment('stock', $detailPart->qty_part);
            }

            // 3. Lakukan koreksi di buku besar (jika ini service klaim)
            if ($service && $service->claimed_from_service_id !== null) {
                $deskripsiKoreksi = "Koreksi/Hapus Part Garansi: {$sparepart->nama_sparepart} (x{$detailPart->qty_part}) untuk Service {$service->kode_service}";

                $this->catatKas(
                    $service,
                    $modalPartYangDihapus, // Uang "masuk" kembali ke kas dengan nilai yang akurat
                    0,
                    $deskripsiKoreksi
                );
                Log::info("Warranty cost REVERSED from ledger: {$modalPartYangDihapus} for service ID {$service->id}");
            }

            // 4. Hapus record part dari service
            $detailPart->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Part berhasil dihapus dan stok telah dikembalikan.'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => 'Data part tidak ditemukan.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Delete Service Part Error: " . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Gagal menghapus part.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store Sparepart Luar (GENERIC - untuk service yang belum selesai)
    public function storeSparepartLuar(Request $request)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        // Validasi input
        $request->validate([
            'kode_services' => 'required|string|exists:sevices,id', // Diubah ke exists:sevices,id
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        DB::beginTransaction(); // PERUBAHAN: Menggunakan transaksi database untuk keamanan
        try {
            $serviceId = $request->kode_services;
            // PERUBAHAN: Ambil model service untuk digunakan di trait kas
            $service = modelServices::findOrFail($serviceId);

            // Membuat record sparepart luar
            $create = DetailPartLuarService::create([
                'kode_services' => $serviceId,
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            // PERUBAHAN: Hitung total biaya dan catat sebagai kas keluar
            $totalCost = $request->harga_part * $request->qty_part;
            $deskripsi = "Biaya Part Luar: {$request->nama_part} (x{$request->qty_part}) untuk Service {$service->kode_service}";
            $this->catatKas($service, 0, $totalCost, $deskripsi);

            DB::commit(); // PERUBAHAN: Commit transaksi

            // Mengembalikan respons sukses dengan data yang disimpan
            return response()->json([
                'message' => 'Sparepart luar added successfully.',
                'data' => $create
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack(); // PERUBAHAN: Rollback jika terjadi error
            // Menangani error jika terjadi pengecualian
            return response()->json([
                'message' => 'Failed to add sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Update Sparepart Luar (GENERIC)
    public function updateSparepartLuar(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        $request->validate([
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        DB::beginTransaction(); // PERUBAHAN: Menggunakan transaksi database
        try {
            $update = DetailPartLuarService::findOrFail($id);
            $service = modelServices::findOrFail($update->kode_services);

            // PERUBAHAN: Hitung biaya lama sebelum diupdate
            $oldCost = $update->harga_part * $update->qty_part;

            // Update data part luar
            $update->update([
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            // PERUBAHAN: Hitung biaya baru dan selisihnya
            $newCost = $request->harga_part * $request->qty_part;
            $costDifference = $newCost - $oldCost;
            $deskripsi = "Update Biaya Part Luar: {$request->nama_part} untuk Service {$service->kode_service}";

            if ($costDifference > 0) {
                // Jika biaya baru lebih besar, catat selisihnya sebagai kas KELUAR
                $this->catatKas($service, 0, $costDifference, $deskripsi);
            } elseif ($costDifference < 0) {
                // Jika biaya baru lebih kecil, catat selisihnya sebagai kas MASUK
                $this->catatKas($service, abs($costDifference), 0, $deskripsi);
            }
            // Jika tidak ada selisih, tidak perlu dicatat

            DB::commit(); // PERUBAHAN: Commit transaksi

            return response()->json(['message' => 'Sparepart luar updated successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // PERUBAHAN: Rollback jika error
            return response()->json([
                'message' => 'Failed to update sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete Sparepart Luar (GENERIC)
    public function deleteSparepartLuar($id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        DB::beginTransaction(); // PERUBAHAN: Menggunakan transaksi database
        try {
            $data = DetailPartLuarService::findOrFail($id);
            $service = modelServices::findOrFail($data->kode_services);

            // PERUBAHAN: Hitung total biaya yang dikembalikan
            $totalCostReversed = $data->harga_part * $data->qty_part;
            $deskripsi = "Koreksi/Hapus Part Luar: {$data->nama_part} (x{$data->qty_part}) untuk Service {$service->kode_service}";

            // PERUBAHAN: Catat sebagai kas MASUK
            $this->catatKas($service, $totalCostReversed, 0, $deskripsi);

            // Hapus data
            $data->delete();

            DB::commit(); // PERUBAHAN: Commit transaksi

            return response()->json(['message' => 'Sparepart luar deleted successfully.'], 200);

        } catch (\Exception $e) {
            DB::rollBack(); // PERUBAHAN: Rollback jika error
            return response()->json([
                'message' => 'Failed to delete sparepart luar.',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * Update service status. This is a critical point for commission calculation.
     * When status becomes 'Selesai', commission is calculated.
     */
    public function updateServiceStatus(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'status_services' => 'required|string|in:Selesai,Proses,Antri',
                'id_teknisi' => 'nullable|exists:users,id',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = modelServices::findOrFail($id);
            $oldStatus = $service->status_services;
            $newStatus = $request->status_services;
            $newTechnicianId = $request->id_teknisi;

            DB::beginTransaction();

            $service->update([
                'status_services' => $newStatus,
                'id_teknisi' => $newTechnicianId,
                'tgl_service' => Carbon::now()->format('Y-m-d H:i:s'),
            ]);

            // Komisi: Hitung atau revert berdasarkan perubahan status
            if ($newStatus === 'Selesai' && $oldStatus !== 'Selesai') {
                $this->performCommissionRecalculation($id);
            } elseif ($newStatus !== 'Selesai' && $oldStatus === 'Selesai') {
                $profitPresentase = ProfitPresentase::where('kode_service', $id)->first();
                if ($profitPresentase) {
                    $teknisi = UserDetail::where('kode_user', $profitPresentase->kode_user)->first();
                    if ($teknisi) {
                        $teknisi->update([
                            'saldo' => $teknisi->saldo - $profitPresentase->profit
                        ]);
                    }
                    $profitPresentase->delete();
                    Log::info("Komisi dibatalkan untuk Service ID: $id karena status berubah dari Selesai.");
                }
            }

            // Kirim WhatsApp jika selesai
            $whatsappStatus = 'Pesan WhatsApp tidak dikirim.';
            if ($newStatus === 'Selesai' && !empty($service->no_telp)) {
                $whatsAppService = app(WhatsAppService::class);
                if (!$whatsAppService->isValidPhoneNumber($service->no_telp)) {
                    $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak valid';
                } else {
                    try {
                        $waResult = $whatsAppService->sendServiceCompletionNotification([
                            'nomor_services' => $service->kode_service,
                            'nama_barang' => $service->type_unit,
                            'no_hp' => $service->no_telp,
                        ]);
                        $whatsappStatus = $waResult['status']
                            ? 'Pesan WhatsApp berhasil dikirim'
                            : 'Pesan WhatsApp gagal dikirim: ' . $waResult['message'];
                    } catch (\Exception $waException) {
                        Log::error("Gagal kirim WA untuk Service ID {$id}: " . $waException->getMessage());
                        $whatsappStatus = 'Pesan WhatsApp gagal dikirim: Terjadi kesalahan sistem';
                    }
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => "Status service berhasil diperbarui ke '{$newStatus}'.",
                'data' => [
                    'service_id' => $id,
                    'status' => $newStatus,
                    'technician_id' => $newTechnicianId,
                    'whatsapp_notification' => $whatsappStatus
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Service tidak ditemukan.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Gagal update status service untuk ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui status service.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }


    public function revertServiceToQueue($id)
    {
        DB::beginTransaction();
        try {
            $service = modelServices::findOrFail($id);

            // Only revert if the service is currently 'Selesai'
            if ($service->status_services !== 'Selesai') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Service can only be reverted if its status is "Selesai". Current status: ' . $service->status_services
                ], 400);
            }

            $commissionReverted = false;
            $commissionAmount = 0;

            // Handle commission rollback if it exists
            $profitPresentase = ProfitPresentase::where('kode_service', $id)->first();
            if ($profitPresentase) {
                $teknisi = UserDetail::where('kode_user', $profitPresentase->kode_user)->first();
                if ($teknisi) {
                    $commissionAmount = $profitPresentase->profit;
                    $teknisi->update(['saldo' => $teknisi->saldo - $commissionAmount]);
                    $commissionReverted = true;
                    Log::info("Commission of {$commissionAmount} reverted for Service ID: {$id}. Technician ID: {$teknisi->kode_user}. New Saldo: {$teknisi->saldo}");
                }
                $profitPresentase->delete();
            }

            // Update service status and clear technician
            $service->update([
                'status_services' => 'Antri',
                'id_teknisi' => null, // Clear technician assignment
                'updated_at' => now(), // Update timestamp
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service successfully reverted to "Antri" status. Technician commission corrected.',
                'data' => [
                    'service_id' => $id,
                    'new_status' => 'Antri',
                    'commission_reverted' => $commissionReverted,
                    'commission_amount' => $commissionAmount,
                ]
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Service not found.',
            ], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Revert Service To Queue Error for Service ID {$id}: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to revert service to queue.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
    /**
     * CRUD Garansi Service API
     * These can be used for both completed and uncompleted services.
     */
    public function storeGaransiService(Request $request)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'kode_garansi' => 'required|string|exists:sevices,kode_service', // Ensure kode_garansi maps to an existing service
                'nama_garansi' => 'required|string|max:255',
                'tgl_mulai_garansi' => 'required|date',
                'tgl_exp_garansi' => 'required|date|after_or_equal:tgl_mulai_garansi', // Changed to after_or_equal
                'catatan_garansi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $service = modelServices::where('kode_service', $request->kode_garansi)->first();
            if (!$service) {
                return response()->json([
                    'success' => false,
                    'message' => 'Service with provided kode_garansi not found.'
                ], 404);
            }

            $create = Garansi::create([
                'type_garansi' => 'service',
                'kode_garansi' => $request->kode_garansi,
                'nama_garansi' => $request->nama_garansi,
                'tgl_mulai_garansi' => $request->tgl_mulai_garansi,
                'tgl_exp_garansi' => $request->tgl_exp_garansi,
                'catatan_garansi' => $request->catatan_garansi ?? '-',
                'user_input' => auth()->user()->id,
                'kode_owner' => $this->getThisUser()->id_upline,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Garansi berhasil ditambahkan',
                'data' => $create
            ], 201);

        } catch (\Exception $e) {
            Log::error("Store Garansi Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getGaransiService($kode_service)
    {
        try {
            \Log::info('Get Warranty Request', [
                'kode_service' => $kode_service,
                'user_id' => auth()->user()->id
            ]);

            // Cari garansi berdasarkan kode service
            $garansi = Garansi::where([
                ['type_garansi', '=', 'service'],
                ['kode_garansi', '=', $kode_service]
            ])->orderBy('created_at', 'desc')->get();

            // Tambahkan informasi status garansi
            $garansiWithStatus = $garansi->map(function($item) {
                $item->status_garansi = $this->getWarrantyStatus($item->tgl_exp_garansi);
                $item->is_expired = now()->gt($item->tgl_exp_garansi);
                $item->days_remaining = now()->diffInDays($item->tgl_exp_garansi, false);
                return $item;
            });

            return response()->json([
                'success' => true,
                'message' => 'Data garansi berhasil diambil',
                'data' => $garansiWithStatus,
                'total' => $garansiWithStatus->count()
            ], 200);

        } catch (\Exception $e) {
            \Log::error('Get Warranty Error', [
                'error' => $e->getMessage(),
                'kode_service' => $kode_service,
                'user_id' => auth()->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    private function getWarrantyStatus($expiry_date)
    {
        $now = now();
        $expiry = Carbon::parse($expiry_date);
        $diffInDays = $now->diffInDays($expiry, false);

        if ($diffInDays < 0) {
            return 'expired';
        } elseif ($diffInDays == 0) {
            return 'expires_today';
        } elseif ($diffInDays <= 7) {
            return 'expires_soon';
        } else {
            return 'active';
        }
    }

    public function updateGaransiService(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            \Log::info('Update Warranty Request', [
                'warranty_id' => $id,
                'request_data' => $request->all(),
                'user_id' => auth()->user()->id
            ]);

            $validator = Validator::make($request->all(), [
                'nama_garansi' => 'required|string|max:255',
                'tgl_mulai_garansi' => 'required|date',
                'tgl_exp_garansi' => 'required|date|after_or_equal:tgl_mulai_garansi',
                'catatan_garansi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = Garansi::findOrFail($id);

            $data->update([
                'nama_garansi' => $request->nama_garansi,
                'tgl_mulai_garansi' => $request->tgl_mulai_garansi,
                'tgl_exp_garansi' => $request->tgl_exp_garansi,
                'catatan_garansi' => $request->catatan_garansi ?? '-',
                'user_input' => auth()->user()->id,
            ]);

            \Log::info('Warranty Updated Successfully', [
                'warranty_id' => $id,
                'updated_by' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Garansi berhasil diupdate',
                'data' => $data
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Garansi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Update Warranty Error', [
                'error' => $e->getMessage(),
                'warranty_id' => $id,
                'user_id' => auth()->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function deleteGaransiService($id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            \Log::info('Delete Warranty Request', [
                'warranty_id' => $id,
                'user_id' => auth()->user()->id
            ]);

            $data = Garansi::findOrFail($id);

            $data->delete();

            \Log::info('Warranty Deleted Successfully', [
                'warranty_id' => $id,
                'deleted_by' => auth()->user()->id
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Garansi berhasil dihapus'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Garansi tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            \Log::error('Delete Warranty Error', [
                'error' => $e->getMessage(),
                'warranty_id' => $id,
                'user_id' => auth()->user()->id ?? null
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    // Method untuk mendapatkan statistik garansi (bonus dari file lama)
    public function getWarrantyStats()
    {
        try {
            $user = auth()->user();
            $userDetail = $this->getThisUser();
            $idUpline = $userDetail->id_upline ?? $user->id;

            $stats = [
                'total_warranties' => Garansi::where('kode_owner', $idUpline)->count(),
                'active_warranties' => Garansi::where('kode_owner', $idUpline)
                    ->where('tgl_exp_garansi', '>', now())
                    ->count(),
                'expired_warranties' => Garansi::where('kode_owner', $idUpline)
                    ->where('tgl_exp_garansi', '<=', now())
                    ->count(),
                'expiring_soon' => Garansi::where('kode_owner', $idUpline)
                    ->whereBetween('tgl_exp_garansi', [now(), now()->addDays(7)])
                    ->count(),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Statistik garansi berhasil diambil',
                'data' => $stats
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    /**
     * CRUD Catatan Service API
     * These can be used for both completed and uncompleted services.
     */
    public function storeCatatanService(Request $request)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'tgl_catatan_service' => 'required|date',
                'kode_services' => 'required|exists:sevices,id',
                'catatan_service' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $create = DetailCatatanService::create([
                'tgl_catatan_service' => $request->tgl_catatan_service,
                'kode_services' => $request->kode_services,
                'kode_user' => auth()->user()->id,
                'catatan_service' => $request->catatan_service ?? '-',
            ]);

            $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                ->where('detail_catatan_services.id', $create->id)
                ->select([
                    'detail_catatan_services.id as id_catatan',
                    'detail_catatan_services.*',
                    'users.name as user_name' // Alias user.name to user_name for clarity
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dibuat',
                'data' => $catatan
            ], 201);

        } catch (\Exception $e) {
            Log::error("Store Catatan Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function updateCatatanService(Request $request, $id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $validator = Validator::make($request->all(), [
                'tgl_catatan_service' => 'required|date',
                'catatan_service' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = DetailCatatanService::findOrFail($id);

            if ($data->kode_user != auth()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk mengubah catatan ini'
                ], 403);
            }

            $data->update([
                'tgl_catatan_service' => $request->tgl_catatan_service,
                'catatan_service' => $request->catatan_service ?? '-',
            ]);

            $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                ->where('detail_catatan_services.id', $id)
                ->select([
                    'detail_catatan_services.id as id_catatan',
                    'detail_catatan_services.*',
                    'users.name as user_name'
                ])
                ->first();

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil diupdate',
                'data' => $catatan
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Update Catatan Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCatatanService($id)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'
            ], 403);
        }

        try {
            $data = DetailCatatanService::findOrFail($id);

            if ($data->kode_user != auth()->user()->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki izin untuk menghapus catatan ini'
                ], 403);
            }

            $data->delete();

            return response()->json([
                'success' => true,
                'message' => 'Catatan berhasil dihapus'
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Catatan tidak ditemukan'
            ], 404);
        } catch (\Exception $e) {
            Log::error("Delete Catatan Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getCatatanService($service_id)
    {
        try {
            $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                ->where('detail_catatan_services.kode_services', $service_id)
                ->select([
                    'detail_catatan_services.id as id_catatan',
                    'detail_catatan_services.*',
                    'users.name as user_name'
                ])
                ->orderBy('detail_catatan_services.tgl_catatan_service', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data catatan berhasil diambil',
                'data' => $catatan
            ], 200);

        } catch (\Exception $e) {
            Log::error("Get Catatan Service Error: " . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get service indicators (has warranty/notes).
     * This function's query `where('status_services', 'Antri')` might be too restrictive
     * if you want indicators for all services, including 'Selesai'.
     * Consider adjusting based on actual need.
     */
    public function getServiceIndicators(Request $request)
{
    try {
        $startTime = microtime(true);

        // Tambah filter owner untuk keamanan data
        $services = modelServices::whereIn('status_services', ['Antri', 'Selesai'])
                                ->where('kode_owner', $this->getThisUser()->id_upline)
                                ->get();

        \Log::info('Services query completed', [
            'count' => $services->count(),
            'owner_id' => $this->getThisUser()->id_upline
        ]);

        if ($services->isEmpty()) {
            return response()->json([
                'success' => true,
                'data' => [],
                'message' => 'No services found'
            ]);
        }

        $indicators = [];
        $warrantyCheckTime = 0;
        $notesCheckTime = 0;

        foreach ($services as $service) {
            // Warranty check
            $warrantyStart = microtime(true);
            $hasWarranty = Garansi::where('kode_garansi', $service->kode_service)
                                 ->where('type_garansi', 'service')
                                 ->exists();
            $warrantyCheckTime += (microtime(true) - $warrantyStart);

            // Notes check
            $notesStart = microtime(true);
            $hasNotes = DetailCatatanService::where('kode_services', $service->id)->exists();
            $notesCheckTime += (microtime(true) - $notesStart);

            $indicators[$service->id] = [
                'has_warranty' => $hasWarranty,
                'has_notes' => $hasNotes
            ];
        }

        $totalTime = microtime(true) - $startTime;

        \Log::info('Service indicators completed', [
            'total_time' => round($totalTime * 1000, 2) . 'ms',
            'warranty_check_time' => round($warrantyCheckTime * 1000, 2) . 'ms',
            'notes_check_time' => round($notesCheckTime * 1000, 2) . 'ms',
            'services_processed' => count($indicators)
        ]);

        return response()->json([
            'success' => true,
            'data' => $indicators,
            'meta' => [
                'processed' => count($indicators),
                'execution_time_ms' => round($totalTime * 1000, 2)
            ]
        ]);

    } catch (\Exception $e) {
        \Log::error('Error in getServiceIndicators', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);

        return response()->json([
            'success' => false,
            'message' => 'Internal server error',
            'error' => config('app.debug') ? $e->getMessage() : 'Something went wrong'
        ], 500);
    }
}


}
