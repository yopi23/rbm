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
        Schema::create('detail_sparepart_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_penjualan');
            $table->string('kode_sparepart');
            $table->string('qty_sparepart');
            $table->string('detail_harga_modal');
            $table->string('detail_harga_jual');
            $table->string('user_input');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('detail_sparepart_penjualans');
    }
};
