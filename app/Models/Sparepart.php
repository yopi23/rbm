<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sparepart extends Model
{
    use HasFactory;
    protected $fillable = [
        'kode_sparepart',
        'kode_kategori',
        'kode_sub_kategori',
        'foto_sparepart',
        'nama_sparepart',
        'desc_sparepart',
        'stok_sparepart',
        'stock_asli',
        'harga_beli',
        'harga_jual',
        'harga_ecer',
        'harga_pasang',
        'kode_owner',
        'kode_spl',
    ];
    // public function detailSparepart()
    // {
    //     return $this->hasMany(DetailSparepartPenjualan::class, 'kode_sparepart', 'id');
    // }
    /**
 * Relasi ke model StockHistory
 */
    public function stockHistory()
    {
        return $this->hasMany(StockHistory::class);
    }

    /**
     * Relasi ke model StockNotification
     */
    public function stockNotifications()
    {
        return $this->hasMany(StockNotification::class);
    }

    /**
     * Method untuk mencatat perubahan stok
     */
    public function logStockChange($change, $referenceType, $referenceId, $notes = null, $userId)
    {
        $stockBefore = $this->stok_sparepart;
        $stockAfter = $stockBefore + $change;

        // Update stok sparepart
        $this->stok_sparepart = $stockAfter;
        $this->save();

        // Buat log history
        return StockHistory::create([
            'sparepart_id' => $this->id,
            'quantity_change' => $change,
            'reference_type' => $referenceType,
            'reference_id' => $referenceId,
            'stock_before' => $stockBefore,
            'stock_after' => $stockAfter,
            'notes' => $notes,
            'user_input' => $userId,
        ]);
    }

    /**
     * Method untuk check dan membuat notifikasi stok rendah
     */
    public function checkAndCreateLowStockNotification($userId)
    {
        if ($this->stok_sparepart <= $this->reorder_point) {
            // Cek apakah sudah ada notifikasi pending
            $existingNotification = $this->stockNotifications()
                                        ->where('status', 'pending')
                                        ->first();

            if (!$existingNotification) {
                return StockNotification::create([
                    'sparepart_id' => $this->id,
                    'current_stock' => $this->stok_sparepart,
                    'reorder_point' => $this->reorder_point,
                    'reorder_quantity' => $this->reorder_quantity,
                    'status' => 'pending',
                    'created_by' => $userId,
                ]);
            }

            return $existingNotification;
        }

        return null;
    }
    // Di file app/Models/Sparepart.php
    public function kategori()
    {
        return $this->belongsTo(KategoriSparepart::class, 'kode_kategori');
    }

    public function subKategori()
    {
        return $this->belongsTo(SubKategoriSparepart::class, 'kode_sub_kategori');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kode_spl');
    }
}
