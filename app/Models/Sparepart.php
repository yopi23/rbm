<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Scopes\ActiveScope;

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
        'cabang_id',
        'kode_spl',
        'is_active',
        'is_visible_on_web',
    ];

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new ActiveScope);
        static::addGlobalScope(new \App\Scopes\CabangScope);
    }
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

    // Relasi dengan harga khusus
    public function hargaKhusus()
    {
        return $this->hasMany(HargaKhusus::class, 'id_sp');
    }

    public function cabang()
    {
        return $this->belongsTo(Cabang::class, 'cabang_id');
    }

    /**
     * Method untuk mencatat perubahan stok
     */
    public function logStockChange($change, $referenceType, $referenceId, $notes = null, $userId, $specificVariantId = null)
    {
        $stockBefore = $this->stok_sparepart;
        $stockAfter = $stockBefore + $change;

        // Update stok sparepart
        $this->stok_sparepart = $stockAfter;
        $this->save();

        // Update Variant Stock
        $variant = null;
        if ($specificVariantId) {
            $variant = ProductVariant::find($specificVariantId);
        } else {
            $variant = $this->variants->first();
        }

        if ($variant) {
            $variant->stock = $stockAfter;
            $variant->save();
        }

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

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'sparepart_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'kode_spl');
    }

    /**
     * Get all photos as an array.
     */
    public function getPhotosAttribute()
    {
        $value = $this->attributes['foto_sparepart'] ?? '-';
        if (empty($value) || $value === '-') {
            return [];
        }

        // Try to decode as JSON
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // If not a JSON array, return it as a single-element array
        return [$value];
    }

    /**
     * Accessor to return the first photo (main photo) when accessing foto_sparepart.
     */
    public function getFotoSparepartAttribute($value)
    {
        if (empty($value) || $value === '-') {
            return '-';
        }

        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            // Return the first photo as the main photo for backward compatibility
            return $decoded[0] ?? '-';
        }

        return $value;
    }

    /**
     * Mutator to encode arrays as JSON, while storing raw strings as-is.
     */
    public function setFotoSparepartAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['foto_sparepart'] = json_encode(array_values($value));
        } else {
            $this->attributes['foto_sparepart'] = $value;
        }
    }
}
