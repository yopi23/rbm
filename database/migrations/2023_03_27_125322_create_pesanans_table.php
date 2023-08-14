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
        Schema::create('pesanans', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_pesanan');
            $table->string('kode_pesanan');
            $table->string('kode_owner');
            $table->string('nama_pemesan');
            $table->string('alamat');
            $table->string('no_telp');
            $table->string('email');
            $table->string('status_pesanan')->default('0');
            $table->string('total_pesanan')->default('0');
            $table->string('total_bayar')->default('0');
            $table->string('catatan_pesanan')->nullable();
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
        Schema::dropIfExists('pesanans');
    }
};
