<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Brand;
use App\Models\ScreenSize;
use App\Models\CameraPosition;

class TGData extends Seeder
{
    /**
     * Run the database seeds.
     */
    // database/seeders/DatabaseSeeder.php
public function run()
{
    // Data Brand
    $brands = ['Samsung', 'Xiaomi', 'Oppo', 'Vivo','Infinix','Realme', 'Apple', 'Huawei'];
    foreach ($brands as $brand) {
        Brand::create(['name' => $brand]);
    }

    // Data Ukuran Layar
    $sizes = ['6.1 inch','6.2 inch','6.3 inch','6.4 inch', '6.5 inch','6.6 inch', '6.7 inch','6.8 inch'];
    foreach ($sizes as $size) {
        ScreenSize::create(['size' => $size]);
    }

    // Data Posisi Kamera
    $positions = [
        ['position' => 'Poni', 'group' => 'Poni'],
        ['position' => 'Tompel Tengah', 'group' => 'Tompel'],
        ['position' => 'Tompel Samping', 'group' => 'Tompel'],
        ['position' => 'Iphone', 'group' => 'iphone'],
    ];
    foreach ($positions as $position) {
        CameraPosition::create($position);
    }
}
}
