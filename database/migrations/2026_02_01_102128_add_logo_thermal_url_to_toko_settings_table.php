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
        Schema::table('toko_settings', function (Blueprint $table) {
            $table->string('logo_thermal_url')->nullable()->after('logo_url');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toko_settings', function (Blueprint $table) {
            $table->dropColumn('logo_thermal_url');
        });
    }
};
