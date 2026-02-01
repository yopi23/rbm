<?php

namespace App\Traits;

use App\Models\StockHistory;
use App\Models\Sparepart;
use App\Models\Shift;

trait StockHistoryTrait
{
    /**
     * Log stock change to history and optionally update the sparepart stock.
     *
     * @param int $sparepartId
     * @param int $quantityChange Positive for addition, negative for deduction (or absolute value handled by logic)
     * @param string $referenceType e.g., 'purchase', 'sale', 'service'
     * @param int $referenceId
     * @param string|null $notes
     * @param int $userId
     * @param int $stockBefore
     * @param int|null $stockAfter If null, calculated from stockBefore + quantityChange
     * @return StockHistory
     */
    public function logStockChange($sparepartId, $quantityChange, $referenceType, $referenceId, $notes = null, $userId, $stockBefore = 0, $stockAfter = null)
    {
        // If stockAfter is not provided, calculate it
        if ($stockAfter === null) {
            $stockAfter = $stockBefore + $quantityChange;
        }

        // Get active shift
        $shiftId = null;
        if ($userId) {
            $activeShift = Shift::getActiveShift($userId);
            if ($activeShift) {
                $shiftId = $activeShift->id;
            }
        }

        // Create log stock history
        return StockHistory::create([
            'sparepart_id' => $sparepartId,
            'quantity_change' => $quantityChange,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
            'user_input' => $userId,
            'shift_id' => $shiftId,
        ]);
    }
}
