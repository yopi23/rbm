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
            $table->string('stock_asli')->nullable()->after('stok_sparepart');
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
            $table->dropColumn('stock_asli');
        });
    }
};
