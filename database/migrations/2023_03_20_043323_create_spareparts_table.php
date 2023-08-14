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
        Schema::create('spareparts', function (Blueprint $table) {
            $table->id();
            $table->string('kode_sparepart');
            $table->string('kode_kategori');
            $table->string('nama_sparepart');
            $table->text('desc_sparepart')->nullable();
            $table->string('harga_beli');
            $table->string('harga_jual');
            $table->string('harga_pasang');
            $table->string('stok_sparepart');
            $table->string('foto_sparepart');
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
        Schema::dropIfExists('spareparts');
    }
};
