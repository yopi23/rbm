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

    /**
     * Ekstrak kata kunci pencarian yang sudah dinormalisasi.
     */
    public static function extractSearchKeywords(string $searchInput): array
    {
        $normalized = str_replace(',', '.', strtolower(trim($searchInput)));
        // Pisahkan kata merek/seri (>= 3 huruf tertentu) yang menempel langsung dengan angka, misal "iphone11" -> "iphone 11"
        $normalized = preg_replace(
            '/\b(iphone|samsung|xiaomi|redmi|infinix|oppo|vivo|realme|tecno|itel|poco|nokia|asus|sony|huawei|honor|google|pixel|note|reno|spark|camon|pova|narzo|hot|pop|tab|pad|mate|nova|find)(\d+)\b/i',
            '$1 $2',
            $normalized
        );
        return array_values(array_filter(preg_split('/\s+/', $normalized), fn($k) => $k !== ''));
    }

    /**
     * Bangun pola REGEXP yang kompatibel di MariaDB 10.x maupun MySQL 8.x
     * agar angka seri/model (misal '11', '12', 'x', 'a12') cocok sebagai token utuh
     * dan tidak bocor ke model lain (misal '11' tidak bocor ke '12', '110', 'A11', 'A2411').
     */
    public static function buildKeywordRegex(string $keyword): string
    {
        $escaped = preg_replace('/([.\\\\+*?\\[\\^\\]$(){}|])/', '\\\\$1', strtolower($keyword));

        if (ctype_digit($keyword)) {
            return '(^|[^a-z0-9]|iphone|note|redmi|reno|hot|spark|camon|poco|tab|pad)' . $escaped . '([^a-z0-9]|$)';
        }

        if (
            preg_match('/\d/', $keyword) ||
            strlen($keyword) <= 2 ||
            in_array($keyword, ['pro', 'max', 'air', 'mini', 'plus', 'lite', 'neo', 'gt', 'fe', 'se', 'xr', 'xs'], true)
        ) {
            return '(^|[^a-z0-9])' . $escaped . '([^a-z0-9]|$)';
        }

        return '(^|[^a-z0-9])' . $escaped;
    }

    /**
     * Tentukan apakah input pencarian layak dicocokkan ke kolom kode_sparepart / SKU.
     * Mencegah angka model seperti '11' atau '12' cocok dengan timestamp di kode_sparepart (misal SP202611...).
     */
    public static function shouldMatchCode(string $searchInput, array $keywords): bool
    {
        $clean = strtolower(trim($searchInput));
        if ($clean === '') {
            return false;
        }
        if (str_starts_with($clean, 'sp') || str_starts_with($clean, 'brg') || str_starts_with($clean, 'prd')) {
            return true;
        }
        if (count($keywords) === 1 && !ctype_digit($clean) && strlen($clean) >= 4) {
            return true;
        }
        return false;
    }

    /**
     * Terapkan filter pencarian sparepart yang presisi pada Eloquent Builder Sparepart.
     */
    public static function applyAccurateSearch($query, string $searchInput, string $tablePrefix = 'spareparts'): array
    {
        $keywords = self::extractSearchKeywords($searchInput);
        $cleanInput = strtolower(trim($searchInput));
        $matchCode = self::shouldMatchCode($searchInput, $keywords);

        if (empty($keywords)) {
            return [];
        }

        $query->where(function ($q) use ($keywords, $cleanInput, $matchCode, $tablePrefix) {
            // Semua keyword WAJIB cocok (AND) pada identitas produk (nama, kategori, subkategori, atau atribut varian)
            $q->where(function ($allKeywordsQuery) use ($keywords, $tablePrefix) {
                foreach ($keywords as $keyword) {
                    $regex = self::buildKeywordRegex($keyword);
                    $allKeywordsQuery->where(function ($kwQuery) use ($regex, $tablePrefix) {
                        $kwQuery->whereRaw("REPLACE(LOWER({$tablePrefix}.nama_sparepart), ',', '.') REGEXP ?", [$regex])
                            ->orWhereHas('kategori', function ($catQ) use ($regex) {
                                $catQ->whereRaw("LOWER(nama_kategori) REGEXP ?", [$regex]);
                            })
                            ->orWhereHas('subKategori', function ($subCatQ) use ($regex) {
                                $subCatQ->whereRaw("LOWER(nama_sub_kategori) REGEXP ?", [$regex]);
                            })
                            ->orWhereHas('variants.attributeValues', function ($attrQ) use ($regex) {
                                $attrQ->whereRaw("REPLACE(LOWER(value), ',', '.') REGEXP ?", [$regex]);
                            });
                    });
                }
            });

            // Pencocokan kode_sparepart hanya untuk pencarian kode/SKU utuh (bukan per-keyword)
            if ($matchCode) {
                $q->orWhereRaw("LOWER({$tablePrefix}.kode_sparepart) LIKE ?", ['%' . $cleanInput . '%']);
            } else {
                $q->orWhereRaw("LOWER({$tablePrefix}.kode_sparepart) = ?", [$cleanInput]);
            }
        });

        return $keywords;
    }

    /**
     * Terapkan urutan relevansi hasil pencarian sparepart.
     */
    public static function applyAccurateOrder($query, array $keywords, string $tablePrefix = 'spareparts')
    {
        if (empty($keywords)) {
            return $query->orderBy("{$tablePrefix}.nama_sparepart", 'asc');
        }

        $col = "REPLACE(LOWER({$tablePrefix}.nama_sparepart), ',', '.')";
        $exactMatchForOrder = implode('%', $keywords);
        $startsPattern = $exactMatchForOrder . '%';
        $containsPattern = '%' . $exactMatchForOrder . '%';

        $numKeywords = array_values(array_filter($keywords, fn($k) => ctype_digit($k)));
        $tierSuffixes = ['pro', 'max', 'plus', 'mini', 'ultra', 'lite'];
        $hasTierInQuery = !empty(array_intersect($keywords, $tierSuffixes));

        if (!empty($numKeywords) && !$hasTierInQuery) {
            $numEscaped = preg_replace('/([.\\\\+*?\\[\\^\\]$(){}|])/', '\\\\$1', $numKeywords[0]);
            $suffixRegex = '(^|[^a-z0-9]|iphone|note|redmi|reno|hot|spark|camon|poco|tab|pad)' . $numEscaped . '[ /\\-]+(pro|max|plus|mini|ultra|lite)([^a-z0-9]|$)';

            return $query->orderByRaw("
                CASE
                    WHEN {$col} LIKE ? AND {$col} NOT REGEXP ? THEN 1
                    WHEN {$col} NOT REGEXP ? THEN 2
                    WHEN {$col} LIKE ? THEN 3
                    WHEN {$col} LIKE ? THEN 4
                    ELSE 5
                END,
                CHAR_LENGTH({$tablePrefix}.nama_sparepart) ASC,
                {$tablePrefix}.nama_sparepart ASC
            ", [$containsPattern, $suffixRegex, $suffixRegex, $startsPattern, $containsPattern]);
        }

        if (in_array('pro', $keywords, true) && !in_array('max', $keywords, true)) {
            $proMaxRegex = '(^|[^a-z0-9])pro[ /\\-]+max([^a-z0-9]|$)';
            return $query->orderByRaw("
                CASE
                    WHEN {$col} LIKE ? AND {$col} NOT REGEXP ? THEN 1
                    WHEN {$col} NOT REGEXP ? THEN 2
                    WHEN {$col} LIKE ? THEN 3
                    WHEN {$col} LIKE ? THEN 4
                    ELSE 5
                END,
                CHAR_LENGTH({$tablePrefix}.nama_sparepart) ASC,
                {$tablePrefix}.nama_sparepart ASC
            ", [$containsPattern, $proMaxRegex, $proMaxRegex, $startsPattern, $containsPattern]);
        }

        return $query->orderByRaw("
            CASE
                WHEN {$col} LIKE ? THEN 1
                WHEN {$col} LIKE ? THEN 2
                ELSE 3
            END,
            CHAR_LENGTH({$tablePrefix}.nama_sparepart) ASC,
            {$tablePrefix}.nama_sparepart ASC
        ", [$startsPattern, $containsPattern]);
    }
}
