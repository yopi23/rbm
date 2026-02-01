<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->bigInteger('harga_beli')->default(0)->after('harga_part');
        });

        // Update existing records: set harga_beli = harga_part (assume 0 profit for past records)
        DB::statement("UPDATE detail_part_luar_services SET harga_beli = harga_part");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->dropColumn('harga_beli');
        });
    }
};
