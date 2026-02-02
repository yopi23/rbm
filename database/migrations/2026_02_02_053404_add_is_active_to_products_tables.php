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
        Schema::table('spareparts', function (Blueprint $table) {
            if (!Schema::hasColumn('spareparts', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('kode_owner');
            }
        });

        Schema::table('handphones', function (Blueprint $table) {
            if (!Schema::hasColumn('handphones', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('kode_owner');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('spareparts', function (Blueprint $table) {
            if (Schema::hasColumn('spareparts', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });

        Schema::table('handphones', function (Blueprint $table) {
            if (Schema::hasColumn('handphones', 'is_active')) {
                $table->dropColumn('is_active');
            }
        });
    }
};
