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
        Schema::create('handphones', function (Blueprint $table) {
            $table->id();
            $table->string('kode_barang');
            $table->string('kode_kategori');
            $table->string('foto_barang');
            $table->string('nama_barang');
            $table->text('desc_barang')->nullable();
            $table->string('merk_barang');
            $table->string('kondisi_barang');
            $table->string('stok_barang')->default('0');
            $table->string('harga_beli_barang');
            $table->string('harga_jual_barang');
            $table->string('status_barang');
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
        Schema::dropIfExists('handphones');
    }
};
