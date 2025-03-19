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
        Schema::create('detail_pembelians', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembelian_id')->constrained()->onDelete('cascade');
            $table->foreignId('sparepart_id')->nullable()->constrained()->onDelete('set null');
            $table->string('nama_item');
            $table->integer('jumlah');
            $table->integer('harga_beli');
            $table->integer('harga_jual')->nullable();
            $table->integer('harga_ecer')->nullable();
            $table->integer('harga_pasang')->nullable();
            $table->integer('total');
            $table->boolean('is_new_item')->default(false); // Menandai apakah ini item baru atau restock
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
        Schema::dropIfExists('detail_pembelians');
    }
};
