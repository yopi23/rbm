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
use App\Models\User; // Ensure User model is imported for teknisi name
use App\Models\UserDetail; // Ensure UserDetail model is imported for teknisi saldo/details
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Models\SubKategoriSparepart;
use App\Models\KategoriSparepart;
use Carbon\Carbon; // Added for date handling


class SparepartApiController extends Controller
{
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
            // Tangkap kata kunci setelah "part"
            $pattern = '/^part[\s,:-]*(.+)$/';
            if (preg_match($pattern, $command, $matches)) {
                $searchQuery = trim($matches[1]);

                // Pisahkan input menjadi kata-kata berdasarkan spasi
                $keywords = array_filter(explode(' ', strtolower($searchQuery)));

                // Buat query pencarian menggunakan DB builder seperti function search
                $queryBuilder = DB::table('spareparts')
                    ->where('kode_owner', '=', $this->getThisUser()->id_upline);

                // Gunakan subquery untuk setiap keyword
                foreach ($keywords as $keyword) {
                    $queryBuilder->where(function ($q) use ($keyword) {
                        $q->where(DB::raw('LOWER(nama_sparepart)'), 'LIKE', '%' . $keyword . '%');
                    });
                }

                // Eksekusi query dengan select fields yang diperlukan
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
                    'stok_sparepart',
                    'created_at',
                    'updated_at'
                ])->get();

                // Enhance data dengan category dan subcategory names
                $enhancedResults = $results->map(function($item) {
                    $category = KategoriSparepart::find($item->kode_kategori);
                    $subcategory = SubKategoriSparepart::find($item->kode_sub_kategori);

                    $item->kategori_nama = $category ? $category->nama_kategori : null;
                    $item->sub_kategori_nama = $subcategory ? $subcategory->nama_sub_kategori : null;

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
                        'harga_jual' => $item->harga_jual ?? $item->detail_harga_part_service,
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
    public function updateService(Request $request, $id)
    {
        try {
            $validatedData = $request->validate([
                'nama_pelanggan' => 'nullable|string|max:255',
                'type_unit' => 'nullable|string|max:255',
                'keterangan' => 'nullable|string',
                'no_telp' => 'nullable|numeric',
                'total_biaya' => 'nullable|numeric|min:0',
                'dp' => 'nullable|numeric|min:0',
            ]);

            $service = ModelServices::findOrFail($id);

            // Store old values to check for changes
            $oldTotalBiaya = $service->total_biaya;
            $oldDp = $service->dp;

            $service->update($validatedData);

            // Trigger commission recalculation if relevant financial fields changed
            // AND the service is already marked as 'Selesai'
            if ($service->status_services === 'Selesai' && (
                (isset($validatedData['total_biaya']) && $validatedData['total_biaya'] != $oldTotalBiaya) ||
                (isset($validatedData['dp']) && $validatedData['dp'] != $oldDp)
            )) {
                $this->performCommissionRecalculation($id);
            }

            return response()->json([
                'message' => 'Service updated successfully',
                'service' => $service,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Service not found',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Server error',
                'error' => $e->getMessage(),
            ], 500);
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

    /**
     * Internal helper method to perform the commission recalculation logic.
     * This method is transactional and expects to be called within an existing transaction
     * or to start its own if not. It's safer if the caller manages the transaction.
     */
    private function performCommissionRecalculation($serviceId)
    {
        Log::info("Internal: Starting performCommissionRecalculation for Service ID: $serviceId");
        // NOTE: This method now expects a transaction to be started by the calling public method.
        // If it's to be called directly, consider adding DB::beginTransaction() and DB::commit()/rollback() here.

        $service = modelServices::findOrFail($serviceId);

        // Fetch all associated spare parts (toko and luar)
        $part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
            ->where('detail_part_services.kode_services', $serviceId)
            ->get(['detail_part_services.detail_harga_part_service', 'detail_part_services.qty_part']);

        $part_luar_toko_service = DetailPartLuarService::where('kode_services', $serviceId)->get(['harga_part', 'qty_part']);

        // Recalculate total_part (harga_sp)
        $total_part = 0;
        foreach ($part_toko_service as $part) {
            $total_part += $part->detail_harga_part_service * $part->qty_part;
        }
        foreach ($part_luar_toko_service as $part) {
            $total_part += $part->harga_part * $part->qty_part;
        }

        // Update the service's harga_sp
        $service->update(['harga_sp' => $total_part]);
        Log::info("Internal: Service Harga SP updated to: $total_part for Service ID: $serviceId");

        // Recalculate technician profit ONLY if service is "Selesai" and a technician is assigned
        if ($service->status_services == 'Selesai' && $service->id_teknisi) {
            $id_teknisi = $service->id_teknisi;
            $presentaseSetting = SalarySetting::where('user_id', $id_teknisi)->first();
            $teknisi = UserDetail::where('kode_user', $id_teknisi)->first();

            if (!$teknisi) {
                Log::warning("Internal: Technician not found for ID: {$id_teknisi} for Service ID: $serviceId. Commission not updated.");
                return [
                    'service_id' => $service->id,
                    'new_harga_sp' => $service->harga_sp,
                    'new_profit' => 0,
                    'warning' => 'Technician not found, commission skipped.'
                ];
            }

            // Retrieve old profit if it exists for this service
            $oldProfitPresentase = ProfitPresentase::where('kode_service', $serviceId)->first();
            $oldProfitAmount = 0;

            if ($oldProfitPresentase) {
                $oldProfitAmount = $oldProfitPresentase->profit;
                // Deduct old profit from technician's current saldo before calculating new profit
                $teknisi->update([
                    'saldo' => $teknisi->saldo - $oldProfitAmount,
                ]);
                Log::info("Internal: Old profit deducted: {$oldProfitAmount} from Technician ID: {$id_teknisi}");
            }

            // Calculate new profit
            $profit = $service->total_biaya - $total_part;
            $fix_profit = 0;

            if ($presentaseSetting && $presentaseSetting->compensation_type == 'percentage') {
                $fix_profit = $profit * $presentaseSetting->percentage_value / 100;
            }
            Log::info("Internal: New calculated profit: {$fix_profit} for Technician ID: {$id_teknisi}");

            // Update or create ProfitPresentase record
            $komisi = ProfitPresentase::updateOrCreate(
                ['kode_service' => $serviceId],
                [
                    'tgl_profit' => now(),
                    'kode_presentase' => $presentaseSetting ? $presentaseSetting->id : null,
                    'kode_user' => $id_teknisi,
                    'profit' => $fix_profit,
                    'saldo' => $teknisi->saldo + $fix_profit, // Provisional saldo
                ]
            );

            // Add the new profit to the technician's saldo
            $teknisi->update([
                'saldo' => $teknisi->saldo + $fix_profit,
            ]);
            Log::info("Internal: Technician new saldo: {$teknisi->saldo} for Technician ID: {$id_teknisi}");

            // Update ProfitPresentase 'saldo' to final value after technician's saldo is updated
            $komisi->update(['saldo' => $teknisi->saldo]);
        }
        Log::info("Internal: performCommissionRecalculation finished for Service ID: $serviceId");

        return [
            'service_id' => $service->id,
            'new_harga_sp' => $service->harga_sp,
            'new_profit' => isset($fix_profit) ? $fix_profit : 0,
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
        try {
            DB::beginTransaction();

            $data = DetailPartServices::findOrFail($detailPartId);

            $serviceId = $data->kode_services;
            $sparepartId = $data->kode_sparepart;
            $deletedQty = (int) $data->qty_part; // Ensure it's an integer

            $sparepart = Sparepart::findOrFail($sparepartId);
            $stok_baru = (int) $sparepart->stok_sparepart + $deletedQty;

            $sparepart->update(['stok_sparepart' => $stok_baru]);
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

            $create = DetailPartLuarService::create([
                'kode_services' => $serviceId,
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

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

            $update->update([
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

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
        DB::beginTransaction();
        try {
            $data = DetailPartLuarService::findOrFail($detailPartLuarId);
            $serviceId = $data->kode_services;

            $data->delete();

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
    public function storeSparepartToko(Request $request) // Original name retained for generic use
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_services' => 'required',
                'kode_sparepart' => 'required',
                'qty_part' => 'required|integer|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
            }

            $sparepart = Sparepart::find($request->kode_sparepart);
            if (!$sparepart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sparepart not found'
                ], 404);
            }

            DB::beginTransaction();
            try {
                $cek = DetailPartServices::where([
                    ['kode_services', '=', $request->kode_services],
                    ['kode_sparepart', '=', $request->kode_sparepart]
                ])->first();

                $currentStockInDb = (int) $sparepart->stok_sparepart;

                if ($cek) {
                    $oldQtyForService = (int) $cek->qty_part;
                    $newTotalQtyForService = $oldQtyForService + $request->qty_part;
                    $projectedNewOverallStock = $currentStockInDb - $request->qty_part; // Only deduct the *newly added* quantity

                    if ($projectedNewOverallStock < 0) {
                         DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal ditambahkan karna Stock: ' . $currentStockInDb
                        ], 400);
                    }

                    $cek->update([
                        'qty_part' => $newTotalQtyForService,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);

                } else {
                    $projectedNewOverallStock = $currentStockInDb - $request->qty_part;

                    if ($projectedNewOverallStock < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Stock tidak Cukup: ' . $currentStockInDb
                        ], 400);
                    }

                    DetailPartServices::create([
                        'kode_services' => $request->kode_services,
                        'kode_sparepart' => $request->kode_sparepart,
                        'detail_modal_part_service' => $sparepart->harga_beli,
                        'detail_harga_part_service' => $sparepart->harga_jual,
                        'qty_part' => $request->qty_part,
                        'user_input' => auth()->user()->id,
                    ]);
                    $sparepart->update(['stok_sparepart' => $projectedNewOverallStock]);
                }

                // IMPORTANT: This generic storeSparepartToko DOES NOT trigger recalculation here.
                // Recalculation is triggered when service status changes to 'Selesai' or through explicit 'recalculateCommission' endpoint.
                // Or via the addPartTokoToCompletedService method for already completed services.

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sparepart transaction successful',
                    'data' => [
                        'kode_services' => $request->kode_services,
                        'kode_sparepart' => $request->kode_sparepart,
                        'qty' => $request->qty_part,
                        'remaining_stock' => $sparepart->stok_sparepart // Use the updated stock
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Store Sparepart Toko Error: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction failed: ' . $e->getMessage(),
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
    public function deleteSparepartToko($id) // Original name retained for generic use
    {
        DB::beginTransaction();
        try {
            $data = DetailPartServices::findOrFail($id);
            $serviceId = $data->kode_services; // Get service ID

            $update_sparepart = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = (int) $update_sparepart->stok_sparepart + (int) $data->qty_part; // Ensure integer casting

            $update_sparepart->update(['stok_sparepart' => $stok_baru]);
            $data->delete();

            // IMPORTANT: This generic deleteSparepartToko DOES NOT trigger recalculation here.
            // Recalculation is triggered when service status changes to 'Selesai' or through explicit 'recalculateCommission' endpoint.
            // Or via the deletePartTokoFromCompletedService method for already completed services.

            DB::commit();

            return response()->json(['message' => 'Sparepart deleted successfully.'], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['message' => 'Sparepart not found.'], 404);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error deleting generic sparepart toko {$id}: " . $e->getMessage());
            return response()->json(['message' => 'Failed to delete sparepart.', 'error' => $e->getMessage()], 500);
        }
    }

    // Store Sparepart Luar (GENERIC - untuk service yang belum selesai)
    public function storeSparepartLuar(Request $request)
    {
        // Validasi input
        $request->validate([
            'kode_services' => 'required|string',
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        try {
            // Membuat record sparepart luar
            $create = DetailPartLuarService::create([
                'kode_services' => $request->kode_services,
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->user()->id,
            ]);

            // Mengembalikan respons sukses dengan data yang disimpan
            return response()->json([
                'message' => 'Sparepart luar added successfully.',
                'data' => $create
            ], 201);
        } catch (\Exception $e) {
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
        $request->validate([
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        $update = DetailPartLuarService::findOrFail($id);

        $update->update([
            'nama_part' => $request->nama_part,
            'harga_part' => $request->harga_part,
            'qty_part' => $request->qty_part,
            'user_input' => auth()->user()->id,
        ]);

        return response()->json(['message' => 'Sparepart luar updated successfully.'], 200);
    }

    // Delete Sparepart Luar (GENERIC)
    public function deleteSparepartLuar($id)
    {
        $data = DetailPartLuarService::findOrFail($id);

        if ($data) {
            $data->delete();
            return response()->json(['message' => 'Sparepart luar deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Sparepart not found.'], 404);
    }


    /**
     * Update service status. This is a critical point for commission calculation.
     * When status becomes 'Selesai', commission is calculated.
     */
    public function updateServiceStatus(Request $request, $id)
{
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
    // Tambah filter owner untuk keamanan data
    $services = modelServices::where('status_services', 'Antri')
                             ->where('kode_owner', $this->getThisUser()->id_upline)
                             ->get();

    $indicators = [];
    foreach ($services as $service) {
        $hasWarranty = Garansi::where('kode_garansi', $service->kode_service)
                             ->where('type_garansi', 'service') // Pastikan hanya garansi service
                             ->exists();
        $hasNotes = DetailCatatanService::where('kode_services', $service->id)->exists();

        $indicators[$service->id] = [
            'has_warranty' => $hasWarranty,
            'has_notes' => $hasNotes
        ];
    }

    return response()->json([
        'success' => true,
        'data' => $indicators
    ]);
}


}
