<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_part_services', function (Blueprint $table) {
            $table->boolean('is_tanggungan_teknisi')->default(false)->after('qty_part');
        });

        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->boolean('is_tanggungan_teknisi')->default(false)->after('qty_part');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_part_services', function (Blueprint $table) {
            $table->dropColumn('is_tanggungan_teknisi');
        });

        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->dropColumn('is_tanggungan_teknisi');
        });
    }
};
