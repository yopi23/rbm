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
        Schema::table('kategori_spareparts', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('nama_kategori');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kategori_spareparts', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
};
