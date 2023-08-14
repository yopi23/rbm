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
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_penjualan')->nullable();
            $table->string('kode_penjualan');
            $table->string('kode_owner');
            $table->string('nama_customer')->default('-');
            $table->text('catatan_customer')->nullable();
            $table->string('user_input');
            $table->string('status_penjualan')->default('0');
            $table->string('total_penjualan');
            $table->string('total_bayar');
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
        Schema::dropIfExists('penjualans');
    }
};
