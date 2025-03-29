<?php
// app/Observers/SparepartSaleObserver.php

namespace App\Observers;

use App\Models\DetailSparepartPenjualan;
use App\Models\Sparepart;
use Illuminate\Support\Facades\Log;

class SparepartSaleObserver
{
    /**
     * Handle the DetailSparepartPenjualan "created" event.
     */
    public function created(DetailSparepartPenjualan $detailSale)
    {
        $sparepart = Sparepart::find($detailSale->kode_sparepart);

        if ($sparepart) {
            try {
                // Log pengurangan stok
                $sparepart->logStockChange(
                    -$detailSale->qty_sparepart,
                    'sale',
                    $detailSale->kode_penjualan,
                    'Pengurangan stok dari penjualan',
                    $detailSale->user_input
                );

                // Check jika stok sudah rendah
                $sparepart->checkAndCreateLowStockNotification($detailSale->user_input);

            } catch (\Exception $e) {
                Log::error('Error updating stock after sale: ' . $e->getMessage());
            }
        } else {
            Log::warning('Sparepart not found when updating stock after sale. ID: ' . $detailSale->kode_sparepart);
        }
    }
}
