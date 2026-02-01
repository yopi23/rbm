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
            $table->string('slug', 50)->unique()->nullable()->after('id_owner');
            $table->string('primary_color', 7)->default('#10B981')->after('logo_url');
            $table->string('secondary_color', 7)->default('#059669')->after('primary_color');
            $table->boolean('public_page_enabled')->default(true)->after('secondary_color');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toko_settings', function (Blueprint $table) {
            $table->dropColumn(['slug', 'primary_color', 'secondary_color', 'public_page_enabled']);
        });
    }
};
