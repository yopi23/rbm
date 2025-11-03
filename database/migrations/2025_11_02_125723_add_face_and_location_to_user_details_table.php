<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            // Kolom untuk Face Recognition (Wajah)
            // Menyimpan embedding wajah (array of numbers) dalam format JSON/string
            $table->longText('face_embedding')->nullable()->after('macrodroid_secret');

            // Kolom untuk Geolocation (Lokasi Kantor/Absen)
            // Menyimpan koordinat lokasi absen default untuk user/toko
            $table->decimal('default_lat', 10, 8)->nullable()->after('face_embedding');
            $table->decimal('default_lon', 11, 8)->nullable()->after('default_lat');
            $table->integer('allowed_radius_m')->default(50)->after('default_lon'); // Radius toleransi dalam meter
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['face_embedding', 'default_lat', 'default_lon', 'allowed_radius_m']);
        });
    }
};
