<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TokoSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'id_owner',
        'slug',
        'nama_toko',
        'alamat_toko',
        'nomor_cs',
        'nomor_info_bot',
        'nota_footer_line1',
        'nota_footer_line2',
        'logo_url',
        'logo_thermal_url',
        'print_logo_on_receipt',
        'primary_color',
        'secondary_color',
        'public_page_enabled',
    ];

    protected $casts = [
        'public_page_enabled' => 'boolean',
        'print_logo_on_receipt' => 'boolean',
    ];

    public function owner()
    {
        return $this->belongsTo(User::class, 'id_owner');
    }

    public static function findBySlug($slug)
    {
        return static::where('slug', $slug)
            ->where('public_page_enabled', true)
            ->first();
    }

    public static function generateUniqueSlug($name)
    {
        $slug = Str::slug($name);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter++;
        }

        return $slug;
    }
}
