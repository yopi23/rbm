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
        Schema::create('restok_spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('kode_restok');
            $table->string('kode_supplier');
            $table->string('tgl_restok');
            $table->string('kode_barang');
            $table->string('jumlah_restok');
            $table->string('status_restok');
            $table->text('catatan_restok');
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
        Schema::dropIfExists('restok_spareparts');
    }
};
