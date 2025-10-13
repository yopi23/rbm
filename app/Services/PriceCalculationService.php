<?php
namespace App\Services;

use App\Models\PriceSetting;

class PriceCalculationService
{

    public function calculate(int $purchasePrice, int $categoryId, $attributeValueIds): ?array
    {
        // Panggil method yang mengembalikan kedua aturan
        $settings = PriceSetting::findBestSetting($categoryId, $attributeValueIds);

        $general = $settings['general'];
        $specific = $settings['specific'];

        // Jika aturan umum saja tidak ada, maka tidak ada dasar perhitungan. Hentikan.
        if (!$general) {
            return null;
        }

        // ====================================================================
        // LOGIKA PEWARISAN (MERGE) YANG DISEMPURNAKAN
        // ====================================================================
        // Gunakan margin spesifik JIKA ADA DAN LEBIH DARI 0. Jika tidak, gunakan margin umum.
        $finalWholesaleMargin = (!empty($specific->wholesale_margin) && $specific->wholesale_margin > 0) ? $specific->wholesale_margin : $general->wholesale_margin;
        $finalRetailMargin    = (!empty($specific->retail_margin) && $specific->retail_margin > 0) ? $specific->retail_margin : $general->retail_margin;
        $finalInternalMargin  = (!empty($specific->internal_margin) && $specific->internal_margin > 0) ? $specific->internal_margin : $general->internal_margin;

        // Ambil default_service_fee (biaya jasa)
        $finalDefaultServiceFee = (!empty($specific->default_service_fee) && $specific->default_service_fee > 0)
                                ? $specific->default_service_fee
                                : $general->default_service_fee;

        // Lakukan kalkulasi dengan nilai final yang sudah digabung
        $wholesalePrice = $purchasePrice + ($purchasePrice * ($finalWholesaleMargin / 100));
        $retailPrice    = $purchasePrice + ($purchasePrice * ($finalRetailMargin / 100));
        $internalPrice  = $purchasePrice + ($purchasePrice * ($finalInternalMargin / 100));

        return [
            'wholesale_price' => $this->roundUpPrice($wholesalePrice, 1000), // dibulatkan ke atas
            'retail_price'    => $this->roundUpPrice($retailPrice, 1000),    // dibulatkan ke atas
            'internal_price'  => $this->roundUpPrice($internalPrice, 1000),  // dibulatkan ke atas
            'default_service_fee' => $finalDefaultServiceFee,
        ];
    }
    private function roundUpPrice($price, int $precision = 1000): int
    {
        if ($precision <= 0) {
            return (int) ceil($price);
        }
        // Menggunakan ceil() untuk pembulatan ke atas
        return (int) (ceil($price / $precision) * $precision);
    }
}
