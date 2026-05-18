<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->boolean('is_potong_kas')->default(true)->after('qty_part');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->dropColumn('is_potong_kas');
        });
    }
};
