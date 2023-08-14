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
        Schema::create('retur_spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_retur_barang');
            $table->string('kode_supplier');
            $table->string('kode_barang');
            $table->string('jumlah_retur');
            $table->text('catatan_retur');
            $table->string('status_retur')->default('0');
            $table->string('user_input');
            $table->string('kode_owner');
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
        Schema::dropIfExists('retur_spareparts');
    }
};
