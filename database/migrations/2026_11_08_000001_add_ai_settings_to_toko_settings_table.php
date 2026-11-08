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
            if (!Schema::hasColumn('toko_settings', 'ai_gemini_api_key')) {
                $table->text('ai_gemini_api_key')->nullable()->after('public_page_enabled');
            }
            if (!Schema::hasColumn('toko_settings', 'ai_model')) {
                $table->string('ai_model')->nullable()->after('ai_gemini_api_key');
            }
            if (!Schema::hasColumn('toko_settings', 'ai_enabled')) {
                $table->boolean('ai_enabled')->default(true)->after('ai_model');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('toko_settings', function (Blueprint $table) {
            $columnsToDrop = [];
            if (Schema::hasColumn('toko_settings', 'ai_gemini_api_key')) {
                $columnsToDrop[] = 'ai_gemini_api_key';
            }
            if (Schema::hasColumn('toko_settings', 'ai_model')) {
                $columnsToDrop[] = 'ai_model';
            }
            if (Schema::hasColumn('toko_settings', 'ai_enabled')) {
                $columnsToDrop[] = 'ai_enabled';
            }
            if (!empty($columnsToDrop)) {
                $table->dropColumn($columnsToDrop);
            }
        });
    }
};
