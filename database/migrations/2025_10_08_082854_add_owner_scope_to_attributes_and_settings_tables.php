<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tambahkan kolom kode_owner ke tabel attributes
        Schema::table('attributes', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_owner')->after('kategori_sparepart_id')->index();
        });

        // Tambahkan kolom kode_owner ke tabel attribute_values
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_owner')->after('attribute_id')->index();
        });

        // Tambahkan kolom kode_owner ke tabel price_settings
        Schema::table('price_settings', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_owner')->after('attribute_value_id')->index();
        });
    }

    public function down(): void
    {
        Schema::table('attributes', function (Blueprint $table) {
            $table->dropColumn('kode_owner');
        });
        Schema::table('attribute_values', function (Blueprint $table) {
            $table->dropColumn('kode_owner');
        });
        Schema::table('price_settings', function (Blueprint $table) {
            $table->dropColumn('kode_owner');
        });
    }
};
