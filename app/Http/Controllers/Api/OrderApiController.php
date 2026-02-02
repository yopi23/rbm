<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\ListOrder;
use App\Models\Shift;
use App\Models\Sparepart;
use App\Models\Supplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\UserDetail;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;

class OrderApiController extends Controller
{
    /**
     * Mendapatkan daftar semua pesanan
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrders(Request $request)
    {
        try {
            $query = Order::with('supplier')
                ->where('kode_owner', $this->getThisUser()->id_upline);

            // Filter berdasarkan tanggal jika parameter start_date diberikan
            if ($request->has('start_date') && !empty($request->start_date)) {
                $startDate = $request->start_date;

                // Validasi format tanggal
                try {
                    $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $startDate);
                    $query->where('created_at', '>=', $parsedDate->startOfDay());
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format tanggal tidak valid. Gunakan format Y-m-d (contoh: 2024-01-01)',
                    ], 400);
                }
            }

            // Filter berdasarkan tanggal akhir jika parameter end_date diberikan
            if ($request->has('end_date') && !empty($request->end_date)) {
                $endDate = $request->end_date;

                try {
                    $parsedDate = \Carbon\Carbon::createFromFormat('Y-m-d', $endDate);
                    $query->where('created_at', '<=', $parsedDate->endOfDay());
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Format tanggal akhir tidak valid. Gunakan format Y-m-d (contoh: 2024-12-31)',
                    ], 400);
                }
            }

            // Filter berdasarkan status jika parameter status diberikan
            if ($request->has('status') && !empty($request->status)) {
                $status = $request->status;
                $validStatuses = ['draft', 'menunggu_pengiriman', 'selesai', 'dibatalkan'];

                if (in_array($status, $validStatuses)) {
                    $query->where('status_order', $status);
                } else {
                    return response()->json([
                        'success' => false,
                        'message' => 'Status tidak valid. Status yang tersedia: ' . implode(', ', $validStatuses),
                    ], 400);
                }
            }

            // Filter berdasarkan pencarian kode_order atau catatan
            if ($request->has('search') && !empty($request->search)) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('kode_order', 'like', '%' . $search . '%')
                      ->orWhere('catatan', 'like', '%' . $search . '%');
                });
            }

            // Pagination jika diperlukan
            if ($request->has('per_page') && is_numeric($request->per_page)) {
                $perPage = min((int)$request->per_page, 100); // Maksimal 100 per halaman
                $orders = $query->orderBy('created_at', 'desc')->paginate($perPage);

                return response()->json([
                    'success' => true,
                    'message' => 'Daftar pesanan berhasil diambil',
                    'data' => $orders->items(),
                    'pagination' => [
                        'current_page' => $orders->currentPage(),
                        'last_page' => $orders->lastPage(),
                        'per_page' => $orders->perPage(),
                        'total' => $orders->total(),
                        'from' => $orders->firstItem(),
                        'to' => $orders->lastItem(),
                    ]
                ]);
            } else {
                // Tanpa pagination - ambil semua data
                $orders = $query->orderBy('created_at', 'desc')->get();

                return response()->json([
                    'success' => true,
                    'message' => 'Daftar pesanan berhasil diambil',
                    'data' => $orders,
                    'total_records' => $orders->count()
                ]);
            }

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders summary/statistics
     */
    public function getOrdersSummary(Request $request)
    {
        try {
            $query = Order::where('kode_owner', $this->getThisUser()->id_upline);

            // Filter berdasarkan tanggal jika diberikan
            if ($request->has('start_date') && !empty($request->start_date)) {
                $startDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->start_date);
                $query->where('created_at', '>=', $startDate->startOfDay());
            }

            if ($request->has('end_date') && !empty($request->end_date)) {
                $endDate = \Carbon\Carbon::createFromFormat('Y-m-d', $request->end_date);
                $query->where('created_at', '<=', $endDate->endOfDay());
            }

            // Hitung statistik berdasarkan status
            $summary = [
                'total_orders' => $query->count(),
                'draft_orders' => (clone $query)->where('status_order', 'draft')->count(),
                'pending_orders' => (clone $query)->where('status_order', 'menunggu_pengiriman')->count(),
                'completed_orders' => (clone $query)->where('status_order', 'selesai')->count(),
                'cancelled_orders' => (clone $query)->where('status_order', 'dibatalkan')->count(),
                'total_items' => (clone $query)->sum('total_item'),
            ];

            return response()->json([
                'success' => true,
                'message' => 'Ringkasan pesanan berhasil diambil',
                'data' => $summary
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil ringkasan pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get recent orders (last 2 months by default)
     */
    public function getRecentOrders(Request $request)
    {
        try {
            // Default 2 bulan terakhir
            $monthsBack = $request->get('months_back', 2);
            $startDate = \Carbon\Carbon::now()->subMonths($monthsBack)->startOfDay();

            $orders = Order::with('supplier')
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->where('created_at', '>=', $startDate)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'message' => "Daftar pesanan {$monthsBack} bulan terakhir berhasil diambil",
                'data' => $orders,
                'filter_info' => [
                    'start_date' => $startDate->format('Y-m-d'),
                    'months_back' => $monthsBack,
                    'total_records' => $orders->count()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil pesanan terbaru',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Mendapatkan daftar supplier
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getSuppliers()
    {
        try {
            $suppliers = Supplier::where('kode_owner', $this->getThisUser()->id_upline)->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar Supplier berhasil diambil',
                'data' => $suppliers
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar Supplier',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan detail pesanan beserta item-itemnya
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function getOrderDetail($id)
    {
        try {
            $order = Order::with(['listOrders', 'supplier'])
                ->where('kode_owner', $this->getThisUser()->id_upline)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Detail pesanan berhasil diambil',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Membuat pesanan baru
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function createOrder(Request $request)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kode_supplier' => 'required|exists:suppliers,id',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
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

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil dibuat',
                'data' => $order
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membuat pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menambahkan item ke pesanan yang ada
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID pesanan
     * @return \Illuminate\Http\JsonResponse
     */
    public function addOrderItem(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }
        // Validasi input
        $validator = Validator::make($request->all(), [
            'nama_item' => 'required|string|max:255',
            'jumlah' => 'required|integer|min:1',
            'sparepart_id' => 'nullable|exists:spareparts,id',
            'harga_perkiraan' => 'nullable|numeric|min:0',
            'catatan_item' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $order = Order::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan sudah diproses dan tidak dapat diubah'
                ], 400);
            }

            // Tambahkan item ke daftar pesanan
            $item = ListOrder::create([
                'order_id' => $id,
                'sparepart_id' => $request->sparepart_id,
                'nama_item' => $request->nama_item,
                'jumlah' => $request->jumlah,
                'status_item' => 'pending',
                'harga_perkiraan' => $request->harga_perkiraan,
                'catatan_item' => $request->catatan_item,
                'kode_owner' => $this->getThisUser()->id_upline,
                'user_input' => $this->getThisUser()->id,
            ]);

            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $id)->count();
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil ditambahkan ke pesanan',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Log error untuk debug
            \Log::error('Error saat menambahkan item: ' . $e->getMessage());
            \Log::error($e->getTraceAsString());

            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item ke pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menambahkan multiple item sekaligus ke pesanan
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id ID pesanan
     * @return \Illuminate\Http\JsonResponse
     */
    public function addMultipleItems(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }
        // Validasi input
        $validator = Validator::make($request->all(), [
            'items' => 'required|array',
            'items.*.nama_item' => 'required|string|max:255',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.sparepart_id' => 'nullable|exists:spareparts,id',
            'items.*.harga_perkiraan' => 'nullable|numeric|min:0',
            'items.*.catatan_item' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            $order = Order::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan sudah diproses dan tidak dapat diubah'
                ], 400);
            }

            $addedItems = [];

            // Tambahkan semua item ke daftar pesanan
            foreach ($request->items as $itemData) {
                $item = ListOrder::create([
                    'order_id' => $id,
                    'sparepart_id' => $itemData['sparepart_id'] ?? null,
                    'nama_item' => $itemData['nama_item'],
                    'jumlah' => $itemData['jumlah'],
                    'status_item' => 'pending',
                    'harga_perkiraan' => $itemData['harga_perkiraan'] ?? null,
                    'catatan_item' => $itemData['catatan_item'] ?? null,
                    'kode_owner' => $this->getThisUser()->id_upline,
                    'user_input' => $this->getThisUser()->id,
                ]);

                $addedItems[] = $item;
            }

            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $id)->count();
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Semua item berhasil ditambahkan ke pesanan',
                'data' => $addedItems
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menambahkan item ke pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Memperbarui data pesanan
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateOrder(Request $request, $id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }
        // Validasi input
        $validator = Validator::make($request->all(), [
            'kode_supplier' => 'required|exists:suppliers,id',
            'catatan' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $order = Order::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan sudah diproses dan tidak dapat diubah'
                ], 400);
            }

            // Update pesanan
            $order->update([
                'kode_supplier' => $request->kode_supplier,
                'catatan' => $request->catatan,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil diperbarui',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memperbarui pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menghapus item dari pesanan
     *
     * @param int $itemId
     * @return \Illuminate\Http\JsonResponse
     */
    public function removeOrderItem($itemId)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }

        try {
            $item = ListOrder::findOrFail($itemId);

            // Verifikasi kepemilikan
            if ($item->kode_owner != $this->getThisUser()->id_upline) {
                return response()->json([
                    'success' => false,
                    'message' => 'Anda tidak memiliki akses ke item ini'
                ], 403);
            }

            $orderId = $item->order_id;

            // Cek apakah pesanan masih dalam status draft
            $order = Order::findOrFail($orderId);
            if ($order->status_order !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan sudah diproses dan tidak dapat diubah'
                ], 400);
            }

            // Hapus item
            $item->delete();

            // Update total item di pesanan
            $order->total_item = ListOrder::where('order_id', $orderId)->count();
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Item berhasil dihapus dari pesanan'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus item',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Finalisasi pesanan
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function finalizeOrder($id)
    {
        $activeShift = Shift::getActiveShift(auth()->user()->id);
        if (!$activeShift) {
            return response()->json(['message' => 'Shift belum dibuka. Silakan buka shift terlebih dahulu.'], 403);
        }

        try {
            $order = Order::where('kode_owner', $this->getThisUser()->id_upline)->findOrFail($id);

            // Cek apakah pesanan masih dalam status draft
            if ($order->status_order !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan sudah diproses sebelumnya'
                ], 400);
            }

            // Cek apakah ada item dalam pesanan
            $itemCount = ListOrder::where('order_id', $id)->count();
            if ($itemCount === 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'Pesanan tidak dapat difinalisasi karena tidak ada item'
                ], 400);
            }

            // Update status pesanan
            $order->status_order = 'menunggu_pengiriman';
            $order->save();

            return response()->json([
                'success' => true,
                'message' => 'Pesanan berhasil difinalisasi dan siap dikirim ke supplier',
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memfinalisasi pesanan',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mendapatkan daftar item stok rendah yang belum ada di pesanan
     *
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getLowStockItems($orderId)
    {
        try {
            // Ambil daftar ID sparepart yang sudah ada dalam order
            $existingItemIds = ListOrder::where('order_id', $orderId)
                ->whereNotNull('sparepart_id')
                ->pluck('sparepart_id')
                ->toArray();

            // Ambil daftar item dengan stok rendah yang belum ditambahkan ke order
            $lowStockItems = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
                ->where('stok_sparepart', '<=', 10)
                ->whereNotIn('id', $existingItemIds)
                ->orderBy('stok_sparepart')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Daftar item stok rendah berhasil diambil',
                'data' => $lowStockItems
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar item stok rendah',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Pencarian sparepart untuk ditambahkan ke pesanan
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function searchSpareparts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'required|string|min:2',
            'order_id' => 'nullable|exists:orders,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $search = $request->search;
            $orderId = $request->order_id;

            // Jika order_id disediakan, filter item yang sudah ada di order
            $existingItemIds = [];
            if ($orderId) {
                $existingItemIds = ListOrder::where('order_id', $orderId)
                    ->whereNotNull('sparepart_id')
                    ->pluck('sparepart_id')
                    ->toArray();
            }

            // Buat query dasar
            $query = Sparepart::where('kode_owner', $this->getThisUser()->id_upline)
                ->where(function($q) use ($search) {
                    $q->where('nama_sparepart', 'like', "%{$search}%")
                      ->orWhere('kode_sparepart', 'like', "%{$search}%");
                });

            // Filter jika ada existing items
            if (!empty($existingItemIds)) {
                $query->whereNotIn('id', $existingItemIds);
            }

            // Eksekusi query
            $results = $query->orderBy('nama_sparepart')
                ->limit(20)
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Pencarian berhasil',
                'results' => $results
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal melakukan pencarian',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method untuk mendapatkan user saat ini
     */
    // public function getThisUser()
    // {
    //     return Auth::user();
    // }

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
