<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Sevices; // Note: Typo in model name as per existing codebase
use App\Models\TokoSetting;
use App\Models\User;
use App\Models\UserDetail;
use App\Models\DetailPartServices;
use App\Models\DetailPartLuarService;
use App\Models\DetailCatatanService;
use App\Models\Sparepart;
use App\Traits\StockHistoryTrait;
use App\Services\WhatsAppService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ServiceBoardController extends Controller
{
    use StockHistoryTrait;

    public function index()
    {
        $page = "Service Board";
        $user = Auth::user();
        $ownerId = $user->userDetail->id_upline ?: $user->id;
        
        // Define statuses
        $statuses = [
            'Antri' => ['label' => 'Antrian', 'color' => 'secondary'],
            'Proses' => ['label' => 'Diproses', 'color' => 'primary'],
            'Selesai' => ['label' => 'Selesai', 'color' => 'success'],
            'Diambil' => ['label' => 'Diambil', 'color' => 'info'],
            'Cancel' => ['label' => 'Batal', 'color' => 'danger'],
        ];

        // Fetch tickets grouped by status
        $tickets = [];
        foreach ($statuses as $key => $meta) {
            $tickets[$key] = Sevices::where('kode_owner', $ownerId)
                ->where('status_services', $key)
                ->with(['teknisi', 'partToko', 'partLuar'])
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        // Teknisi = jabatan 3
        $teknisi = User::whereHas('userDetail', function($q) {
            $q->where('jabatan', 3);
        })->get();

        return view('admin.page.service_board.index', compact('page', 'statuses', 'tickets', 'teknisi'));
    }

    public function create()
    {
        $page = "Terima Service Baru";
        $user = Auth::user();
        $ownerId = $user->userDetail->id_upline ?: $user->id;
        
        // Generate Code
        $count = Sevices::where('kode_owner', $ownerId)->count();
        $kode_service = 'SV' . date('Ymd') . rand(100, 999) . $count;
        
        // Teknisi = jabatan 3
        $teknisi = User::whereHas('userDetail', function($q) {
            $q->where('jabatan', 3);
        })->get();

        return view('admin.page.service_board.create', compact('page', 'kode_service', 'teknisi'));
    }

    public function store(Request $request)
    {
        // Check Active Shift
        $activeShift = \App\Models\Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return redirect()->back()->with('error', 'Shift belum dibuka. Silakan buka shift terlebih dahulu.');
        }

        $request->validate([
            'nama_pelanggan' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'type_unit' => 'required|string|max:255',
            'keterangan' => 'nullable|string',
            'tipe_sandi' => 'nullable|string',
            'isi_sandi' => 'nullable|string',
            'dp' => 'nullable|numeric',
        ]);

        $user = Auth::user();
        $ownerId = $user->userDetail->id_upline ?: $user->id;

        $service = new Sevices();
        $service->kode_service = $request->kode_service; // Should be validated or regenerated to be safe
        $service->kode_owner = $ownerId;
        $service->tgl_service = Carbon::now();
        $service->nama_pelanggan = $request->nama_pelanggan;
        $service->no_telp = $request->no_telp;
        $service->type_unit = $request->type_unit;
        $service->keterangan = $request->keterangan;
        $service->tipe_sandi = $request->tipe_sandi;
        $service->isi_sandi = $request->isi_sandi;
        $service->dp = $request->dp ?? 0;
        $service->total_biaya = $request->dp ?? 0; // Initial total usually equals DP or 0
        $service->status_services = 'Antri';
        $service->id_teknisi = $request->id_teknisi;
        
        // Save data_unit with accessories
        $service->data_unit = json_encode(['kelengkapan' => $request->kelengkapan ?? '-']);
        
        $service->save();

        return redirect()->route('service_board.index')->with('success', 'Service accepted successfully');
    }

    public function updateStatus(Request $request)
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
            'id' => 'required|exists:sevices,id',
            'status' => 'required|string',
        ]);

        try {
            $service = Sevices::findOrFail($request->id);
            $oldStatus = $service->status_services;
            $service->status_services = $request->status;
            
            // If status is 'Diambil', maybe set date taken?
            // If status is 'Selesai', maybe set date finished?
            
            $service->save();

            // WhatsApp Notification Logic
            $waMessage = '';
            if ($request->status === 'Selesai' && $oldStatus !== 'Selesai' && !empty($service->no_telp)) {
                try {
                    $whatsAppService = app(WhatsAppService::class);
                    
                    if ($whatsAppService->isValidPhoneNumber($service->no_telp)) {
                        $waResult = $whatsAppService->sendServiceCompletionNotification([
                            'nomor_services' => $service->kode_service,
                            'nama_barang' => $service->type_unit,
                            'no_hp' => $service->no_telp,
                        ]);
                        
                        if ($waResult['status']) {
                            $waMessage = '. WA Notification sent.';
                        }
                    }
                } catch (\Exception $e) {
                    // Log error but don't fail the request
                    \Illuminate\Support\Facades\Log::error('WA Error: ' . $e->getMessage());
                }
            }

            return response()->json(['success' => true, 'message' => 'Status updated successfully' . $waMessage]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getServiceDetails($id)
    {
        $service = Sevices::with(['teknisi', 'partToko.sparepart', 'partLuar', 'catatan.user'])->findOrFail($id);
        
        // Format for JSON response
        return response()->json([
            'success' => true,
            'data' => $service
        ]);
    }

    public function searchSparepartJson(Request $request)
    {
        $search = $request->q;
        $user = Auth::user();
        $ownerId = $user->userDetail->id_upline ?: $user->id;

        $query = Sparepart::where('kode_owner', $ownerId);
        
        if($search) {
            $query->where('nama_sparepart', 'LIKE', "%{$search}%");
        }

        $parts = $query->limit(20)->get();
        
        // Format for Select2
        $results = $parts->map(function($item) {
            return [
                'id' => $item->id,
                'text' => $item->nama_sparepart . ' (Stok: ' . $item->stok_sparepart . ') - Rp ' . number_format($item->harga_jual),
                'stock' => $item->stok_sparepart
            ];
        });

        return response()->json(['results' => $results]);
    }

    // --- Sparepart Management ---

    public function addSparepartToko(Request $request)
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
            'kode_services' => 'required|exists:sevices,id', // Note: input name usually 'service_id' but logic uses 'kode_services' (FK to id)
            'kode_sparepart' => 'required|exists:spareparts,id',
            'qty_part' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            $cek = DetailPartServices::where([
                ['kode_services', '=', $request->kode_services], 
                ['kode_sparepart', '=', $request->kode_sparepart]
            ])->first();

            $update_sparepart = Sparepart::findOrFail($request->kode_sparepart);
            $qty_used = $request->qty_part;

            if ($cek) {
                // Update existing
                $qty_baru = $cek->qty_part + $qty_used;
                $cek->update([
                    'qty_part' => $qty_baru,
                    'user_input' => auth()->id(),
                ]);
            } else {
                // Create new
                DetailPartServices::create([
                    'kode_services' => $request->kode_services,
                    'kode_sparepart' => $request->kode_sparepart,
                    'detail_modal_part_service' => $update_sparepart->harga_beli,
                    'detail_harga_part_service' => $update_sparepart->harga_jual,
                    'qty_part' => $qty_used,
                    'user_input' => auth()->id(),
                ]);
            }

            // Deduct stock
            $current_db_stock = $update_sparepart->stok_sparepart;
            $stok_baru = $current_db_stock - $qty_used;
            $update_sparepart->update(['stok_sparepart' => $stok_baru]);

            // Log Stock History
            if (method_exists($this, 'logStockChange')) {
                $this->logStockChange(
                    $update_sparepart->id,
                    -$qty_used,
                    'service_add_qty',
                    $request->kode_services,
                    'Service Board Add Part: ' . $request->kode_services,
                    auth()->id(),
                    $current_db_stock,
                    $stok_baru
                );
            }

            // Update Total Biaya Service
            $this->recalculateTotalCost($request->kode_services);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Sparepart added successfully']);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function deleteSparepartToko($id)
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

            $data = DetailPartServices::findOrFail($id);
            $serviceId = $data->kode_services;

            // Restore stock
            $update_sparepart = Sparepart::findOrFail($data->kode_sparepart);
            $stok_baru = $update_sparepart->stok_sparepart + $data->qty_part;
            $update_sparepart->update(['stok_sparepart' => $stok_baru]);

            // Log Stock History (optional for restore, but good practice)
            // ...

            $data->delete();

            // Update Total Biaya Service
            $this->recalculateTotalCost($serviceId);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Sparepart removed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function addSparepartLuar(Request $request)
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
            'kode_services' => 'required|exists:sevices,id',
            'nama_part' => 'required|string',
            'harga_part' => 'required|numeric|min:0',
            'qty_part' => 'required|integer|min:1',
        ]);

        try {
            DB::beginTransaction();

            DetailPartLuarService::create([
                'kode_services' => $request->kode_services,
                'nama_part' => $request->nama_part,
                'harga_part' => $request->harga_part,
                'qty_part' => $request->qty_part,
                'user_input' => auth()->id(),
            ]);

            $this->recalculateTotalCost($request->kode_services);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'External part added successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

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

        try {
            DB::beginTransaction();
            $data = DetailPartLuarService::findOrFail($id);
            $serviceId = $data->kode_services;
            $data->delete();
            
            $this->recalculateTotalCost($serviceId);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'External part removed successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function recalculateTotalCost($serviceId)
    {
        $service = Sevices::with(['partToko', 'partLuar'])->find($serviceId);
        if (!$service) return;

        $totalPartToko = 0;
        foreach ($service->partToko as $part) {
            $totalPartToko += $part->qty_part * $part->detail_harga_part_service;
        }

        $totalPartLuar = 0;
        foreach ($service->partLuar as $part) {
            $totalPartLuar += $part->qty_part * $part->harga_part;
        }

        // Update 'harga_sp' which tracks total parts cost.
        $service->harga_sp = $totalPartToko + $totalPartLuar;
        
        $service->save();
    }

    public function updateServiceDetails(Request $request)
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
            'id' => 'required|exists:sevices,id',
            'nama_pelanggan' => 'required|string|max:255',
            'no_telp' => 'required|string|max:20',
            'type_unit' => 'required|string|max:255',
            'kelengkapan' => 'nullable|string',
            'keterangan' => 'nullable|string',
            'tipe_sandi' => 'nullable|string',
            'isi_sandi' => 'nullable|string',
            'dp' => 'nullable|numeric',
            'id_teknisi' => 'nullable|exists:users,id',
        ]);

        try {
            $service = Sevices::findOrFail($request->id);
            
            $service->nama_pelanggan = $request->nama_pelanggan;
            $service->no_telp = $request->no_telp;
            $service->type_unit = $request->type_unit;
            $service->keterangan = $request->keterangan;
            $service->tipe_sandi = $request->tipe_sandi;
            $service->isi_sandi = $request->isi_sandi;
            $service->dp = $request->dp ?? 0;
            $service->id_teknisi = $request->id_teknisi;
            $service->priority = $request->priority ?? $service->priority;
            
            // Update data_unit/kelengkapan
            $dataUnit = json_decode($service->data_unit, true) ?? [];
            $dataUnit['kelengkapan'] = $request->kelengkapan ?? '-';
            $service->data_unit = json_encode($dataUnit);

            $service->save();

            return response()->json(['success' => true, 'message' => 'Service details updated successfully']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function sendWhatsappNotification(Request $request, $id)
    {
        try {
            $service = Sevices::findOrFail($id);
            $whatsAppService = app(WhatsAppService::class);
            
            if (!$whatsAppService->isValidPhoneNumber($service->no_telp)) {
                return response()->json(['success' => false, 'message' => 'Invalid phone number'], 400);
            }

            // Construct message based on status
            $message = "Halo {$service->nama_pelanggan},\n\n";
            $message .= "Informasi Service di RBM Store:\n";
            $message .= "Unit: {$service->type_unit}\n";
            $message .= "Status: {$service->status_services}\n";
            $message .= "Total Biaya: Rp " . number_format($service->total_biaya) . "\n\n";
            
            if ($service->status_services == 'Selesai') {
                $message .= "Unit Anda sudah selesai diperbaiki dan siap diambil. Mohon membawa nota saat pengambilan.\n";
            } else {
                $message .= "Kami akan menginformasikan kembali update selanjutnya.\n";
            }
            
            $message .= "Terima kasih.";

            $waResult = $whatsAppService->sendMessage($service->no_telp, $message);
            
            if ($waResult['status']) {
                return response()->json(['success' => true, 'message' => 'WhatsApp notification sent successfully']);
            } else {
                return response()->json(['success' => false, 'message' => 'Failed to send WhatsApp: ' . $waResult['message']], 500);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Logic for printing via Web API (returning data for JS to handle)
    public function getPrintData($id, $type = 'ticket')
    {
        $service = Sevices::with(['teknisi', 'partToko.sparepart', 'partLuar'])->findOrFail($id);
        
        // Get store settings
        $user = Auth::user();
        $ownerId = $user->userDetail->id_upline ?: $user->id;
        $setting = TokoSetting::where('id_owner', $ownerId)->first();

        $storeName = $setting->nama_toko ?? 'RBM Store';
        $storeAddress = $setting->alamat_toko ?? '-';
        $storePhone = $setting->nomor_cs ?? '-';
        $storeLogo = $setting->logo_url ? asset('storage/' . $setting->logo_url) : null;
        $printLogo = $setting->print_logo_on_receipt ?? true;

        // Prepare items/parts data
        $items = [];
        foreach($service->partToko as $part) {
            $items[] = [
                'name' => $part->sparepart->nama_sparepart ?? 'Unknown Part',
                'qty' => $part->qty_part,
                'price' => $part->detail_harga_part_service,
                'subtotal' => $part->qty_part * $part->detail_harga_part_service
            ];
        }
        foreach($service->partLuar as $part) {
            $items[] = [
                'name' => $part->nama_part ?? 'Part Luar',
                'qty' => $part->qty_part,
                'price' => $part->harga_part,
                'subtotal' => $part->qty_part * $part->harga_part
            ];
        }

        // Parse data_unit for additional info
        $dataUnit = json_decode($service->data_unit, true);
        $accessories = $dataUnit['kelengkapan'] ?? '-';

        // Prepare data structure expected by printer-thermal.js
        $data = [
            'tenant' => [
                'name' => $storeName,
                'address' => $storeAddress,
                'phone' => $storePhone,
                'logo_url' => $storeLogo,
                'print_logo_on_receipt' => $printLogo,
                'footer1' => $setting->nota_footer_line1 ?? null,
                'footer2' => $setting->nota_footer_line2 ?? null,
            ],
            'ticket' => [
                'ticket_number' => $service->kode_service,
                'priority' => $service->priority,
                'issue_description' => $service->keterangan,
                'estimated_cost' => $service->total_biaya,
                'final_cost' => $service->total_biaya, // Used for warranty
                'deposit' => $service->dp,
                'accessories' => $accessories,
                'completed_at' => $service->status_services === 'Selesai' || $service->status_services === 'Diambil' ? $service->updated_at : null,
            ],
            'date' => $service->tgl_service,
            'customer' => [
                'name' => $service->nama_pelanggan,
                'phone' => $service->no_telp,
            ],
            'device' => [
                'brand' => $service->type_unit,
                'model' => '', // Empty to avoid duplication
                'condition_notes' => '-',
            ],
            'technician_name' => $service->teknisi->name ?? '-',
            'tracking_url' => ($setting->slug ? url('/cek/' . $setting->slug) : url('/')) . '?kode=' . $service->kode_service . '&tab=' . ($type === 'warranty' ? 'garansi' : 'service'),
            'items' => $items,
            'parts' => $items, // Alias for warranty print
            'total' => $service->total_biaya,
            'status' => $service->status_services,
            'security' => [
                'type' => $service->tipe_sandi,
                'code' => $service->isi_sandi
            ],
            // Warranty specific
            'warranty' => [
                'duration_days' => 7, // Default 7 days, could be dynamic
                'terms' => $setting->nota_footer_line1 ?? 'Garansi berlaku untuk kerusakan yang sama. Tidak berlaku jika segel rusak atau terkena air.',
            ],
            // Sticker specific
            'sticker_height_mm' => request()->label_height ?? 15,
            'label_width_mm' => request()->label_width ?? 54,
            'use_gap_mode' => true,
            'paperWidth' => 48, // Default for 80mm paper
        ];

        return response()->json([
            'success' => true,
            'type' => $type,
            'print_data' => $data
        ]);
    }
}
