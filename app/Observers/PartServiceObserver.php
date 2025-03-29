<?php
// app/Observers/PartServiceObserver.php

namespace App\Observers;

use App\Models\DetailPartServices;
use App\Models\Sparepart;
use Illuminate\Support\Facades\Log;

class PartServiceObserver
{
    /**
     * Handle the DetailPartServices "created" event.
     */
    public function created(DetailPartServices $detailService)
    {
        $sparepart = Sparepart::find($detailService->kode_sparepart);

        if ($sparepart) {
            try {
                // Log pengurangan stok
                $sparepart->logStockChange(
                    -$detailService->qty_part,
                    'service',
                    $detailService->kode_services,
                    'Pengurangan stok untuk service',
                    $detailService->user_input
                );

                // Check jika stok sudah rendah
                $sparepart->checkAndCreateLowStockNotification($detailService->user_input);

            } catch (\Exception $e) {
                Log::error('Error updating stock after service: ' . $e->getMessage());
            }
        } else {
            Log::warning('Sparepart not found when updating stock after service. ID: ' . $detailService->kode_sparepart);
        }
    }
}
