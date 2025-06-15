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
use App\Models\User;
use App\Models\UserDetail;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Models\SubKategoriSparepart;
use App\Models\KategoriSparepart;

class SparepartApiController extends Controller
{

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

    // detail service
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
                ])
                ->map(function ($item) {
                    return [
                        'detail_part_id' => $item->detail_part_id,
                        'qty_part' => $item->qty_part,
                        'harga_jual' => $item->detail_harga_part_service,
                        'nama_sparepart' => $item->nama_sparepart ?? 'Unknown Part', // Default jika nama sparepart null
                        'kode_sparepart' => $item->kode_sparepart ?? 'Unknown Code', // Default jika kode sparepart null
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
                        'nama_part' => $item->nama_part ?? 'Unknown Part', // Default jika nama part null
                        'qty_part' => $item->qty_part,
                        'harga_part' => $item->harga_part,
                    ];
                });

            // Gabungkan data menjadi format JSON yang rapi
            $details = [
                'service' => [
                    'id' => $service->id,
                    'kode_service' => $service->kode_service,
                    'customer_name' => $service->nama_pelanggan,
                    'type_unit' => $service->type_unit,
                    'status_services' => $service->status_services,
                    'total_biaya' => $service->total_biaya,
                    'dp' => $service->dp,
                    'harga_sp' => $service->harga_sp,
                    'created_at' => $service->created_at,
                    'updated_at' => $service->updated_at,
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
    public function updateService(Request $request, $id)
    {
        try {
            // Validasi input (jika ada)
            $validatedData = $request->validate([
                'nama_pelanggan' => 'nullable|string|max:255',
                'type_unit' => 'nullable|string|max:255',
                'keterangan' => 'nullable|string',
                'no_telp' => 'nullable|numeric',
                'total_biaya' => 'nullable|numeric|min:0',
                'dp' => 'nullable|numeric|min:0',
            ]);

            // Cari service berdasarkan ID
            $service = ModelServices::findOrFail($id);

            // Update hanya data yang diberikan dalam request
            $service->update($validatedData);

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


    // Store Sparepart Toko
    public function storeSparepartToko(Request $request)
    {
        try {
            // Validate request
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

            // Check sparepart existence and stock
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

                if ($cek) {
                    // Update existing record
                    $qty_baru = $cek->qty_part + $request->qty_part;

                    // Calculate new stock and check if it would go negative
                    $stok_awal = $sparepart->stok_sparepart + $cek->qty_part;
                    $stok_baru = $stok_awal - $qty_baru;

                    if ($stok_baru < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Gagal ditambahkan karna Stock: ' . $sparepart->stok_sparepart
                        ], 400);
                    }

                    $cek->update([
                        'qty_part' => $qty_baru,
                        'user_input' => auth()->user()->id,
                    ]);

                    $sparepart->update([
                        'stok_sparepart' => $stok_baru,
                    ]);
                } else {
                    // Create new record
                    // Check if stock would go negative
                    $stok_baru = $sparepart->stok_sparepart - $request->qty_part;

                    if ($stok_baru < 0) {
                        DB::rollBack();
                        return response()->json([
                            'success' => false,
                            'message' => 'Stock tidak Cukup: ' . $sparepart->stok_sparepart
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

                    $sparepart->update([
                        'stok_sparepart' => $stok_baru,
                    ]);
                }

                DB::commit();

                return response()->json([
                    'success' => true,
                    'message' => 'Sparepart transaction successful',
                    'data' => [
                        'kode_services' => $request->kode_services,
                        'kode_sparepart' => $request->kode_sparepart,
                        'qty' => $request->qty_part,
                        'remaining_stock' => $stok_baru
                    ]
                ], 200);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction failed',
                    'error' => $e->getMessage()
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Delete service
    public function delete_service($id)
    {
        $data = modelServices::findOrFail($id);

        $data->delete();
        if ($data) {
            return response()->json(['message' => 'Sparepart deleted successfully.'], 200);
        }
        return response()->json(['message' => 'Data not found.'], 404);
    }
    // end Delete service
    // Delete Sparepart Toko
    public function deleteSparepartToko($id)
    {
        $data = DetailPartServices::findOrFail($id);

        if ($data) {
            $update_sparepart = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update_sparepart->stok_sparepart + $data->qty_part;

            $update_sparepart->update([
                'stok_sparepart' => $stok_baru,
            ]);

            $data->delete();

            return response()->json(['message' => 'Sparepart deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Sparepart not found.'], 404);
    }

    // Store Sparepart Luar
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

    // Update Sparepart Luar
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

    // Delete Sparepart Luar
    public function deleteSparepartLuar($id)
    {
        $data = DetailPartLuarService::findOrFail($id);

        if ($data) {
            $data->delete();
            return response()->json(['message' => 'Sparepart luar deleted successfully.'], 200);
        }

        return response()->json(['message' => 'Sparepart not found.'], 404);
    }

    public function updateServiceStatus(Request $request, $id)
    {
        try {
            $request->validate([
                'status_services' => 'required|string|in:Selesai', // Validasi status harus "Selesai"
                'id_teknisi' => 'required|exists:user_details,kode_user', // Validasi ID teknisi
            ]);

            $update = modelServices::findOrFail($id); // Cari service berdasarkan ID
            $id_teknisi = $request->id_teknisi;

            if ($request->status_services === 'Selesai') {
                // Ambil detail part toko dan luar toko
                $part_toko_service = DetailPartServices::join('spareparts', 'detail_part_services.kode_sparepart', '=', 'spareparts.id')
                    ->where('detail_part_services.kode_services', $id)
                    ->get(['detail_part_services.*', 'spareparts.*']);

                $part_luar_toko_service = DetailPartLuarService::where('kode_services', $id)->get();

                // Ambil presentase teknisi
                $presentase = SalarySetting::where('user_id', $id_teknisi)->first();

                // Hitung total part
                $total_part = 0;
                foreach ($part_toko_service as $a) {
                    $total_part += $a->harga_jual * $a->qty_part;
                }
                foreach ($part_luar_toko_service as $b) {
                    $total_part += $b->harga_part * $b->qty_part;
                }

                // Update total part ke service
                $update->update([
                    'harga_sp' => $total_part,
                    'status_services' => $request->status_services, // Ubah status menjadi "Selesai"
                    'id_teknisi' => $request->id_teknisi,
                ]);

                if ($presentase->compensation_type=='percentage') {
                    // Periksa apakah komisi sudah pernah dibuat untuk service ini
                    $komisi_exist = ProfitPresentase::where('kode_service', $id)->exists();

                    if (!$komisi_exist) {
                        // Hitung profit teknisi
                        $profit = $update->total_biaya - $total_part;
                        $fix_profit = $profit * $presentase->percentage_value / 100;

                        // Ambil data teknisi
                        $teknisi = UserDetail::where('kode_user', $id_teknisi)->first();

                        // Simpan data profit ke ProfitPresentase
                        $komisi = ProfitPresentase::create([
                            'tgl_profit' => now(),
                            'kode_service' => $id,
                            'kode_presentase' => $presentase->id,
                            'kode_user' => $id_teknisi,
                            'profit' => $fix_profit,
                            'saldo' => $teknisi->saldo,
                        ]);

                        if ($komisi) {
                            // Update saldo teknisi
                            $teknisi->update([
                                'saldo' => $teknisi->saldo + $fix_profit,
                            ]);
                        }
                    }
                }

                // Status WhatsApp notification
                $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak tersedia';

                // Kirim notifikasi WhatsApp jika nomor telepon tersedia
                if (!empty($update->no_telp)) {
                    // Inject WhatsAppService
                    $whatsAppService = app(WhatsAppService::class);

                    // Validasi nomor telepon terlebih dahulu
                    if (!$whatsAppService->isValidPhoneNumber($update->no_telp)) {
                        $whatsappStatus = 'Pesan WhatsApp tidak dikirim: Nomor telepon tidak valid';
                    } else {
                        try {
                            // Kirim notifikasi
                            $waResult = $whatsAppService->sendServiceCompletionNotification([
                                'nomor_services' => $update->kode_service,
                                'nama_barang' => $update->type_unit,
                                'no_hp' => $update->no_telp,
                            ]);

                            if ($waResult['status']) {
                                $whatsappStatus = 'Pesan WhatsApp berhasil dikirim';
                            } else {
                                $whatsappStatus = 'Pesan WhatsApp gagal dikirim: ' . $waResult['message'];
                            }
                        } catch (\Exception $waException) {
                            // Log error tapi jangan batalkan transaksi utama
                            \Log::error("Failed to send WhatsApp notification: " . $waException->getMessage(), [
                                'service_id' => $id,
                                'exception' => $waException
                            ]);

                            $whatsappStatus = 'Pesan WhatsApp gagal dikirim: Terjadi kesalahan sistem';
                        }
                    }
                }

                return response()->json([
                    'success' => true,
                    'message' => "Service status updated to 'Selesai' successfully by technician {$id_teknisi}.",
                    'data' => [
                        'service_id' => $id,
                        'technician_id' => $id_teknisi,
                        'nama' => $teknisi->fullname,
                        'status' => $request->status_services,
                        'whatsapp_notification' => $whatsappStatus
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Invalid status provided.',
                'data' => null,
            ], 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update service status.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    //CRUD Garansi Service API
    public function storeGaransiService(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'kode_garansi' => 'required|string',
                'nama_garansi' => 'required|string|max:255',
                'tgl_mulai_garansi' => 'required|date',
                'tgl_exp_garansi' => 'required|date|after:tgl_mulai_garansi',
                'catatan_garansi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation error',
                    'errors' => $validator->errors()
                ], 422);
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

            if ($create) {
                return response()->json([
                    'success' => true,
                    'message' => 'Garansi berhasil ditambahkan',
                    'data' => $create
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan garansi'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
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

// Helper method untuk menentukan status garansi
private function getWarrantyStatus($expiry_date)
{
    $now = now();
    $expiry = \Carbon\Carbon::parse($expiry_date);
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

// Method untuk update garansi (jika diperlukan)
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
            'tgl_exp_garansi' => 'required|date|after:tgl_mulai_garansi',
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

        // Check ownership atau permission (opsional)
        // if ($data->user_input != auth()->user()->id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized to update this warranty'
        //     ], 403);
        // }

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

// Method untuk menghapus garansi (jika diperlukan)
public function deleteGaransiService($id)
{
    try {
        \Log::info('Delete Warranty Request', [
            'warranty_id' => $id,
            'user_id' => auth()->user()->id
        ]);

        $data = Garansi::findOrFail($id);

        // Check ownership atau permission (opsional)
        // if ($data->user_input != auth()->user()->id) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'Unauthorized to delete this warranty'
        //     ], 403);
        // }

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

// Method untuk mendapatkan statistik garansi (bonus)
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

    //CRUD Catatan Service API
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

            if ($create) {
                // Get the created note with user information
                $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                    ->where('detail_catatan_services.id', $create->id)
                    ->select([
                        'detail_catatan_services.id as id_catatan',
                        'detail_catatan_services.*',
                        'users.*'
                    ])
                    ->first();

                return response()->json([
                    'success' => true,
                    'message' => 'Catatan berhasil dibuat',
                    'data' => $catatan
                ], 201);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat catatan'
            ], 500);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
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

            // Check if the current user is the owner of this note
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

            // Get updated note with user information
            $catatan = DetailCatatanService::join('users', 'detail_catatan_services.kode_user', '=', 'users.id')
                ->where('detail_catatan_services.id', $id)
                ->select([
                    'detail_catatan_services.id as id_catatan',
                    'detail_catatan_services.*',
                    'users.*'
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
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function deleteCatatanService($id)
    {
        try {
            $data = DetailCatatanService::findOrFail($id);

            // Check if the current user is the owner of this note
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
            return response()->json([
                'success' => false,
                'message' => 'Server error',
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
                    'users.*'
                ])
                ->orderBy('detail_catatan_services.tgl_catatan_service', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Data catatan berhasil diambil',
                'data' => $catatan
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
