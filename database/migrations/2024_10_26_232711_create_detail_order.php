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
        Schema::create('detail_order', function (Blueprint $table) {
            $table->id();
            $table->string('id_order');
            $table->string('id_barang')->nullable();
            $table->string('id_pesanan')->nullable();
            $table->string('id_kategori')->nullable();
            $table->string('nama_barang');
            $table->string('qty');
            $table->string('beli_terakhir')->nullable();
            $table->string('pasang_terakhir')->nullable();
            $table->string('ecer_terakhir')->nullable();
            $table->string('jasa_terakhir')->nullable();
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
        Schema::dropIfExists('detail_order');
    }
};
