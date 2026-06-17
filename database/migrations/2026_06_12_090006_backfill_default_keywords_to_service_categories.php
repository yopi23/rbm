<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update "Ringan"
        DB::table('service_categories')
            ->where('nama', 'like', '%ringan%')
            ->where(function ($query) {
                $query->whereNull('keywords')->orWhere('keywords', '');
            })
            ->update([
                'keywords' => 'lcd, screen, baterai, battery, casing, backdoor, backcover, lens, kamera, camera, speaker, buzzer, flexibel, con, konektor, connector, tombol, button, onoff, volume'
            ]);

        // Update "Sedang"
        DB::table('service_categories')
            ->where('nama', 'like', '%sedang%')
            ->where(function ($query) {
                $query->whereNull('keywords')->orWhere('keywords', '');
            })
            ->update([
                'keywords' => 'touchscreen, glass, software, flash, bypass, unlock, frp, root, charging port, lampu, backlight, ic'
            ]);

        // Update "Berat"
        DB::table('service_categories')
            ->where('nama', 'like', '%berat%')
            ->where(function ($query) {
                $query->whereNull('keywords')->orWhere('keywords', '');
            })
            ->update([
                'keywords' => 'mati total, matot, short, cpu, ram, emmc, ufs, reball, mesin, motherboard, jumper, signal, rf, audio, wifi, baseband, power'
            ]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // No action needed for down migration as it's a data backfill
    }
};
