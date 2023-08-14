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
        Schema::create('sevices', function (Blueprint $table) {
            $table->id();
            $table->string('kode_service');
            $table->string('tgl_service');
            $table->string('nama_pelanggan');
            $table->string('no_telp');
            $table->string('type_unit');
            $table->text('keterangan');
            $table->string('total_biaya')->default('0');
            $table->string('dp')->default('0');
            $table->string('id_teknisi')->nullable();
            $table->string('kode_pengambilan')->nullable();
            $table->string('status_services');
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
        Schema::dropIfExists('sevices');
    }
};
