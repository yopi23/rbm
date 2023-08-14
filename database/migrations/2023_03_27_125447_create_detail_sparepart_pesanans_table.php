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
        Schema::create('detail_sparepart_pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pesanan');
            $table->string('kode_sparepart');
            $table->string('detail_modal_pesan');
            $table->string('detail_harga_pesan');
            $table->string('qty_sparepart');
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
        Schema::dropIfExists('detail_sparepart_pesanans');
    }
};
