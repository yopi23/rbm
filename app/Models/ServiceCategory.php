<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'nama',
        'persentase',
        'kode_warna',
        'is_default',
        'is_active',
        'kode_owner',
        'keywords',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
        'persentase' => 'integer',
    ];

    public function jobs()
    {
        return $this->hasMany(ServiceJob::class, 'service_category_id');
    }

    /**
     * Determine category ID based on job name keywords.
     */
    public static function determineCategoryFromJobName($jobName, $userUpline)
    {
        // 1. Fetch default category
        $defaultCategory = self::where('kode_owner', $userUpline)
            ->where('is_active', true)
            ->where('is_default', true)
            ->first();

        if (!$defaultCategory) {
            $defaultCategory = self::where('kode_owner', $userUpline)
                ->where('is_active', true)
                ->first();
        }

        $defaultCategoryId = $defaultCategory ? $defaultCategory->id : null;

        if (empty($jobName)) {
            return $defaultCategoryId;
        }

        $jobNameLower = strtolower($jobName);

        // 2. Get all active categories sorted by percentage descending
        $activeCategoriesForMatching = self::where('kode_owner', $userUpline)
            ->where('is_active', true)
            ->orderBy('persentase', 'desc')
            ->get();

        foreach ($activeCategoriesForMatching as $cat) {
            if (!empty($cat->keywords)) {
                $keywordsArray = explode(',', $cat->keywords);
                foreach ($keywordsArray as $kw) {
                    $trimmedKw = trim(strtolower($kw));
                    if (!empty($trimmedKw) && str_contains($jobNameLower, $trimmedKw)) {
                        return $cat->id;
                    }
                }
            }
        }

        return $defaultCategoryId;
    }

    public function toArray()
    {
        $array = parent::toArray();
        
        if (app()->runningInConsole()) {
            return $array;
        }

        $user = auth()->user();
        $isAdmin = $user && $user->userDetail && $user->userDetail->jabatan == '1';
        
        if (!$isAdmin) {
            if (isset($array['persentase'])) {
                $array['persentase'] = 0;
            }
        }
        
        return $array;
    }
}
