<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shift;
use App\Models\StockHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class ShiftApiController extends Controller
{
    private function getOwnerId()
    {
        $user = Auth::user();
        if(!$user) return null;
        if($user->userDetail && $user->userDetail->jabatan == '1') return $user->id;
        return $user->userDetail ? $user->userDetail->id_upline : null;
    }

    /**
     * Get Current Active Shift Status
     */
    public function status(Request $request)
    {
        $user = Auth::user();
        $activeShift = Shift::getActiveShift($user->id);

        if (!$activeShift) {
            return response()->json([
                'success' => true,
                'status' => 'closed',
                'message' => 'No active shift found',
                'data' => null
            ]);
        }

        return response()->json([
            'success' => true,
            'status' => 'open',
            'message' => 'Active shift found',
            'data' => $this->formatShiftData($activeShift)
        ]);
    }

    /**
     * Open a new shift
     */
    public function open(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'modal_awal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $activeShift = Shift::getActiveShift($user->id);

        if ($activeShift) {
            return response()->json([
                'success' => false,
                'message' => 'Store already has an active shift.',
                'data' => $this->formatShiftData($activeShift)
            ], 400);
        }

        $shift = Shift::create([
            'kode_owner' => $this->getOwnerId(),
            'user_id' => $user->id,
            'start_time' => now(),
            'modal_awal' => $request->modal_awal,
            'status' => 'open',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift opened successfully',
            'data' => $this->formatShiftData($shift)
        ], 201);
    }

    /**
     * Close the active shift
     */
    public function close(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'saldo_akhir_aktual' => 'required|numeric|min:0',
            'note' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = Auth::user();
        $shift = Shift::getActiveShift($user->id);

        if (!$shift) {
            return response()->json([
                'success' => false,
                'message' => 'No active shift found to close.',
            ], 404);
        }

        // Calculate financials
        $cashIn = $shift->kasPerusahaan()->sum('debit');
        $cashOut = $shift->kasPerusahaan()->sum('kredit');
        $expectedCash = $shift->modal_awal + $cashIn - $cashOut;

        // Generate Snapshot Report
        $sparepartReport = $this->getSparepartAnalysis($shift);
        
        $reportData = [
            'cash_in' => $cashIn,
            'cash_out' => $cashOut,
            'expected_cash' => $expectedCash,
            'sparepart_analysis' => $sparepartReport,
            'closed_at' => now()->toDateTimeString(),
            'closed_by' => $user->id
        ];

        $saldoAkhirAktual = $request->saldo_akhir_aktual;
        $selisih = $saldoAkhirAktual - $expectedCash;

        $shift->update([
            'end_time' => now(),
            'saldo_akhir_sistem' => $expectedCash,
            'saldo_akhir_aktual' => $saldoAkhirAktual,
            'selisih' => $selisih,
            'status' => 'closed',
            'note' => $request->input('note'),
            'report_data' => json_encode($reportData),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Shift closed successfully',
            'data' => [
                'shift' => $shift,
                'summary' => [
                    'modal_awal' => $shift->modal_awal,
                    'cash_in' => $cashIn,
                    'cash_out' => $cashOut,
                    'expected_cash' => $expectedCash,
                    'actual_cash' => $saldoAkhirAktual,
                    'difference' => $selisih,
                    'sparepart_analysis' => $sparepartReport
                ]
            ]
        ]);
    }

    /**
     * Helper to format shift data
     */
    private function formatShiftData($shift)
    {
        return [
            'id' => $shift->id,
            'user_id' => $shift->user_id,
            'start_time' => $shift->start_time,
            'modal_awal' => $shift->modal_awal,
            'status' => $shift->status,
            'created_at' => $shift->created_at,
        ];
    }

    /**
     * Helper for sparepart analysis (Copied from Admin\ShiftController)
     */
    private function getSparepartAnalysis($shift)
    {
        $data = [];

        $startTime = $shift->start_time;
        $endTime = $shift->end_time ?? now();
        $userId = $shift->user_id;

        $histories = StockHistory::with('sparepart')
            ->where(function($query) use ($shift, $userId, $startTime, $endTime) {
                $query->where('shift_id', $shift->id)
                      ->orWhere(function($q) use ($userId, $startTime, $endTime) {
                          $q->whereNull('shift_id')
                            ->where('user_input', $userId)
                            ->whereBetween('created_at', [$startTime, $endTime]);
                      });
            })
            ->orderBy('created_at', 'asc')
            ->get();

        foreach ($histories as $history) {
            $id = $history->sparepart_id;
            
            if (!$history->sparepart) continue;

            if (!isset($data[$id])) {
                $data[$id] = [
                    'nama' => $history->sparepart->nama_sparepart,
                    'used' => 0,
                    'stock_in' => 0,
                    'current_stock' => 0,
                    'initial_stock_est' => 0
                ];
            }

            if ($history->quantity_change < 0) {
                $data[$id]['used'] += abs($history->quantity_change);
            } else {
                $data[$id]['stock_in'] += $history->quantity_change;
            }

            $data[$id]['current_stock'] = $history->stock_after;
        }

        foreach($data as &$item) {
            $item['initial_stock_est'] = $item['current_stock'] + $item['used'] - $item['stock_in'];
            $item['sisa'] = $item['current_stock'];
        }

        return array_values($data); // Return array instead of object with IDs as keys
    }
}
