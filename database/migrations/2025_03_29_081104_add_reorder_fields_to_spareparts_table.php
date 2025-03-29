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
        Schema::table('spareparts', function (Blueprint $table) {
            $table->integer('reorder_point')->default(5)->after('stok_sparepart');
            $table->integer('reorder_quantity')->default(10)->after('reorder_point');
            $table->integer('lead_time_days')->default(7)->after('reorder_quantity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spareparts', function (Blueprint $table) {
            $table->dropColumn(['reorder_point', 'reorder_quantity', 'lead_time_days']);
        });
    }
};
