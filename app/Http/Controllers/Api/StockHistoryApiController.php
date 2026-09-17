<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StockHistory;
use App\Models\Sparepart;
use App\Models\Sevices;
use App\Models\Pembelian;
use App\Models\Penjualan;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StockHistoryApiController extends Controller
{
    /**
     * Get aggregated stock movements report by date range
     */
    public function getStockMovements(Request $request)
    {
        try {
            $startDate = $request->input('start_date', Carbon::now()->subDays(30)->format('Y-m-d'));
            $endDate = $request->input('end_date', Carbon::now()->format('Y-m-d'));
            $search = trim($request->input('search', ''));
            $perPage = (int) $request->input('per_page', 20);
            $page = (int) $request->input('page', 1);

            $startDateTime = $startDate . ' 00:00:00';
            $endDateTime = $endDate . ' 23:59:59';

            // Base query for stock_history within the period
            $query = DB::table('stock_history')
                ->join('spareparts', 'stock_history.sparepart_id', '=', 'spareparts.id')
                ->whereBetween('stock_history.created_at', [$startDateTime, $endDateTime]);

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('spareparts.nama_sparepart', 'like', "%{$search}%")
                      ->orWhere('spareparts.kode_sparepart', 'like', "%{$search}%");
                });
            }

            // Calculate overall summary across all matched records
            $overallSummary = (clone $query)->select(
                DB::raw('COUNT(DISTINCT stock_history.sparepart_id) as total_items'),
                DB::raw('SUM(CASE WHEN stock_history.quantity_change > 0 THEN stock_history.quantity_change ELSE 0 END) as grand_total_in'),
                DB::raw('SUM(CASE WHEN stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as grand_total_out'),
                DB::raw("SUM(CASE WHEN stock_history.reference_type IN ('pembelian', 'purchase') AND stock_history.quantity_change > 0 THEN stock_history.quantity_change ELSE 0 END) as grand_pembelian"),
                DB::raw("SUM(CASE WHEN stock_history.reference_type LIKE '%service%' AND stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as grand_service"),
                DB::raw("SUM(CASE WHEN stock_history.reference_type IN ('sale', 'penjualan') AND stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as grand_sale")
            )->first();

            // Aggregated query per sparepart
            $movementsQuery = (clone $query)
                ->select(
                    'stock_history.sparepart_id',
                    'spareparts.nama_sparepart',
                    'spareparts.kode_sparepart',
                    'spareparts.stok_sparepart as current_stock',
                    DB::raw('COUNT(stock_history.id) as total_tx'),
                    DB::raw('SUM(CASE WHEN stock_history.quantity_change > 0 THEN stock_history.quantity_change ELSE 0 END) as total_in'),
                    DB::raw('SUM(CASE WHEN stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as total_out'),
                    DB::raw("SUM(CASE WHEN stock_history.reference_type IN ('pembelian', 'purchase') AND stock_history.quantity_change > 0 THEN stock_history.quantity_change ELSE 0 END) as in_pembelian"),
                    DB::raw("SUM(CASE WHEN stock_history.reference_type LIKE '%service%' AND stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as out_service"),
                    DB::raw("SUM(CASE WHEN stock_history.reference_type IN ('sale', 'penjualan') AND stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) as out_sale"),
                    DB::raw("SUM(CASE WHEN stock_history.reference_type LIKE '%opname%' THEN stock_history.quantity_change ELSE 0 END) as opname_adjust"),
                    DB::raw("SUM(CASE WHEN stock_history.reference_type LIKE '%transfer%' THEN stock_history.quantity_change ELSE 0 END) as transfer_adjust")
                )
                ->groupBy('stock_history.sparepart_id', 'spareparts.nama_sparepart', 'spareparts.kode_sparepart', 'spareparts.stok_sparepart')
                ->orderByDesc(DB::raw('SUM(CASE WHEN stock_history.quantity_change < 0 THEN ABS(stock_history.quantity_change) ELSE 0 END) + SUM(CASE WHEN stock_history.quantity_change > 0 THEN stock_history.quantity_change ELSE 0 END)'));

            $paginated = $movementsQuery->paginate($perPage, ['*'], 'page', $page);

            // Fetch earliest stock_before and latest stock_after for the items on this page
            $sparepartIds = collect($paginated->items())->pluck('sparepart_id')->toArray();

            $earliestHistories = [];
            $latestHistories = [];

            if (!empty($sparepartIds)) {
                // Earliest entry in range
                $earliestList = DB::table('stock_history')
                    ->whereIn('sparepart_id', $sparepartIds)
                    ->whereBetween('created_at', [$startDateTime, $endDateTime])
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->groupBy('sparepart_id');

                foreach ($earliestList as $spId => $items) {
                    $earliestHistories[$spId] = $items->first()->stock_before ?? 0;
                    $latestHistories[$spId] = $items->last()->stock_after ?? 0;
                }
            }

            $items = collect($paginated->items())->map(function ($row) use ($earliestHistories, $latestHistories) {
                $spId = $row->sparepart_id;
                $stokAwal = $earliestHistories[$spId] ?? ($row->current_stock + $row->total_out - $row->total_in);
                $stokAkhir = $latestHistories[$spId] ?? $row->current_stock;

                return [
                    'sparepart_id' => $row->sparepart_id,
                    'nama_sparepart' => $row->nama_sparepart,
                    'kode_sparepart' => $row->kode_sparepart,
                    'stok_sekarang' => (int) $row->current_stock,
                    'stok_awal' => (int) $stokAwal,
                    'stok_akhir' => (int) $stokAkhir,
                    'total_masuk' => (int) $row->total_in,
                    'total_keluar' => (int) $row->total_out,
                    'total_transaksi' => (int) $row->total_tx,
                    'breakdown' => [
                        'pembelian' => (int) $row->in_pembelian,
                        'service' => (int) $row->out_service,
                        'penjualan' => (int) $row->out_sale,
                        'opname' => (int) $row->opname_adjust,
                        'transfer' => (int) $row->transfer_adjust,
                    ],
                ];
            });

            return response()->json([
                'success' => true,
                'summary' => [
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'total_items' => (int) ($overallSummary->total_items ?? 0),
                    'total_masuk' => (int) ($overallSummary->grand_total_in ?? 0),
                    'total_keluar' => (int) ($overallSummary->grand_total_out ?? 0),
                    'total_pembelian' => (int) ($overallSummary->grand_pembelian ?? 0),
                    'total_service' => (int) ($overallSummary->grand_service ?? 0),
                    'total_penjualan' => (int) ($overallSummary->grand_sale ?? 0),
                ],
                'data' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'data' => $items,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data mutasi stok: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get detailed stock history timeline for a specific sparepart
     */
    public function getSparepartDetailHistory(Request $request, $id)
    {
        try {
            $sparepart = Sparepart::find($id);
            if (!$sparepart) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sparepart tidak ditemukan',
                ], 404);
            }

            $startDate = $request->input('start_date');
            $endDate = $request->input('end_date');
            $referenceType = $request->input('type');
            $perPage = (int) $request->input('per_page', 50);
            $page = (int) $request->input('page', 1);

            $query = StockHistory::where('sparepart_id', $id);

            if ($startDate && $endDate) {
                $query->whereBetween('created_at', [
                    $startDate . ' 00:00:00',
                    $endDate . ' 23:59:59',
                ]);
            }

            if ($referenceType && $referenceType !== 'all') {
                if ($referenceType === 'service') {
                    $query->where('reference_type', 'like', '%service%');
                } elseif ($referenceType === 'pembelian') {
                    $query->whereIn('reference_type', ['pembelian', 'purchase']);
                } elseif ($referenceType === 'penjualan') {
                    $query->whereIn('reference_type', ['sale', 'penjualan', 'refund']);
                } elseif ($referenceType === 'opname') {
                    $query->where('reference_type', 'like', '%opname%');
                } else {
                    $query->where('reference_type', $referenceType);
                }
            }

            // Calculate period summary for this sparepart
            $periodSummary = (clone $query)->select(
                DB::raw('SUM(CASE WHEN quantity_change > 0 THEN quantity_change ELSE 0 END) as total_in'),
                DB::raw('SUM(CASE WHEN quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as total_out'),
                DB::raw("SUM(CASE WHEN reference_type IN ('pembelian', 'purchase') AND quantity_change > 0 THEN quantity_change ELSE 0 END) as in_pembelian"),
                DB::raw("SUM(CASE WHEN reference_type LIKE '%service%' AND quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as out_service"),
                DB::raw("SUM(CASE WHEN reference_type IN ('sale', 'penjualan') AND quantity_change < 0 THEN ABS(quantity_change) ELSE 0 END) as out_sale"),
                DB::raw("SUM(CASE WHEN reference_type LIKE '%opname%' THEN quantity_change ELSE 0 END) as opname_adjust")
            )->first();

            $paginated = $query->orderBy('created_at', 'desc')
                              ->paginate($perPage, ['*'], 'page', $page);

            // Pre-fetch reference details and user names
            $userIds = collect($paginated->items())->pluck('user_input')->filter()->unique()->toArray();
            $usersMap = User::whereIn('id', $userIds)->pluck('name', 'id')->toArray();

            // Collect reference IDs to enrich
            $serviceRefs = [];
            $purchaseRefs = [];
            $saleRefs = [];

            foreach ($paginated->items() as $item) {
                $refType = strtolower($item->reference_type ?? '');
                $refId = $item->reference_id;

                if (!$refId) continue;

                if (str_contains($refType, 'service')) {
                    $serviceRefs[] = $refId;
                } elseif (str_contains($refType, 'pembelian') || str_contains($refType, 'purchase')) {
                    $purchaseRefs[] = $refId;
                } elseif (str_contains($refType, 'sale') || str_contains($refType, 'penjualan')) {
                    $saleRefs[] = $refId;
                }
            }

            $servicesMap = [];
            if (!empty($serviceRefs)) {
                $foundServices = Sevices::where(function ($q) use ($serviceRefs) {
                    $q->whereIn('id', $serviceRefs)
                      ->orWhereIn('kode_service', $serviceRefs);
                })->get(['id', 'kode_service', 'nama_pelanggan', 'type_unit', 'status_services']);

                foreach ($foundServices as $srv) {
                    $servicesMap[$srv->id] = $srv;
                    $servicesMap[$srv->kode_service] = $srv;
                }
            }

            $purchasesMap = [];
            if (!empty($purchaseRefs)) {
                $foundPurchases = Pembelian::where(function ($q) use ($purchaseRefs) {
                    $q->whereIn('id', $purchaseRefs)
                      ->orWhereIn('kode_pembelian', $purchaseRefs);
                })->get(['id', 'kode_pembelian', 'supplier', 'tanggal_pembelian']);

                foreach ($foundPurchases as $pb) {
                    $purchasesMap[$pb->id] = $pb;
                    $purchasesMap[$pb->kode_pembelian] = $pb;
                }
            }

            $salesMap = [];
            if (!empty($saleRefs)) {
                $foundSales = Penjualan::where(function ($q) use ($saleRefs) {
                    $q->whereIn('id', $saleRefs)
                      ->orWhereIn('kode_penjualan', $saleRefs);
                })->get(['id', 'kode_penjualan', 'nama_customer', 'tgl_penjualan']);

                foreach ($foundSales as $pj) {
                    $salesMap[$pj->id] = $pj;
                    $salesMap[$pj->kode_penjualan] = $pj;
                }
            }

            // Map timeline items
            $timeline = collect($paginated->items())->map(function ($item) use ($usersMap, $servicesMap, $purchasesMap, $salesMap) {
                $refType = strtolower($item->reference_type ?? '');
                $refId = $item->reference_id;

                $userName = $usersMap[$item->user_input] ?? $item->user_input ?? '-';

                $extra = null;
                $displayType = 'Lainnya';
                $activityColor = 'grey';

                if (str_contains($refType, 'pembelian') || str_contains($refType, 'purchase')) {
                    $displayType = 'Pembelian';
                    $activityColor = 'green';
                    if ($refId && isset($purchasesMap[$refId])) {
                        $p = $purchasesMap[$refId];
                        $extra = [
                            'kode' => $p->kode_pembelian,
                            'supplier' => $p->supplier ?? '-',
                            'tanggal' => $p->tanggal_pembelian,
                        ];
                    }
                } elseif (str_contains($refType, 'service')) {
                    $displayType = 'Pemakaian Service';
                    $activityColor = 'blue';
                    if ($refId && isset($servicesMap[$refId])) {
                        $s = $servicesMap[$refId];
                        $extra = [
                            'kode_service' => $s->kode_service,
                            'nama_pelanggan' => $s->nama_pelanggan ?? '-',
                            'type_unit' => $s->type_unit ?? '-',
                            'status' => $s->status_services ?? '-',
                        ];
                    }
                } elseif (str_contains($refType, 'sale') || str_contains($refType, 'penjualan')) {
                    $displayType = 'Penjualan';
                    $activityColor = 'orange';
                    if ($refId && isset($salesMap[$refId])) {
                        $pj = $salesMap[$refId];
                        $extra = [
                            'kode_penjualan' => $pj->kode_penjualan,
                            'customer' => $pj->nama_customer ?? '-',
                            'tanggal' => $pj->tgl_penjualan,
                        ];
                    }
                } elseif (str_contains($refType, 'opname')) {
                    $displayType = 'Stock Opname';
                    $activityColor = 'purple';
                } elseif (str_contains($refType, 'transfer')) {
                    $displayType = 'Transfer Cabang';
                    $activityColor = 'teal';
                } elseif (str_contains($refType, 'refund')) {
                    $displayType = 'Refund / Retur';
                    $activityColor = 'cyan';
                }

                return [
                    'id' => $item->id,
                    'date' => Carbon::parse($item->created_at)->format('Y-m-d H:i:s'),
                    'formatted_date' => Carbon::parse($item->created_at)->translatedFormat('d M Y, H:i'),
                    'quantity_change' => (int) $item->quantity_change,
                    'stock_before' => (int) $item->stock_before,
                    'stock_after' => (int) $item->stock_after,
                    'reference_type' => $item->reference_type,
                    'display_type' => $displayType,
                    'activity_color' => $activityColor,
                    'reference_id' => $item->reference_id,
                    'notes' => $item->notes,
                    'user_name' => $userName,
                    'extra_detail' => $extra,
                ];
            });

            return response()->json([
                'success' => true,
                'sparepart' => [
                    'id' => $sparepart->id,
                    'nama_sparepart' => $sparepart->nama_sparepart,
                    'kode_sparepart' => $sparepart->kode_sparepart,
                    'stok_sparepart' => (int) $sparepart->stok_sparepart,
                ],
                'summary' => [
                    'total_masuk' => (int) ($periodSummary->total_in ?? 0),
                    'total_keluar' => (int) ($periodSummary->total_out ?? 0),
                    'in_pembelian' => (int) ($periodSummary->in_pembelian ?? 0),
                    'out_service' => (int) ($periodSummary->out_service ?? 0),
                    'out_sale' => (int) ($periodSummary->out_sale ?? 0),
                    'opname_adjust' => (int) ($periodSummary->opname_adjust ?? 0),
                ],
                'data' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'total' => $paginated->total(),
                    'per_page' => $paginated->perPage(),
                    'data' => $timeline,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil riwayat stok sparepart: ' . $e->getMessage(),
            ], 500);
        }
    }
}
