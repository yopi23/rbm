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
        Schema::create('pengeluaran_tokos', function (Blueprint $table) {
            $table->id();
            $table->string('tanggal_pengeluaran');
            $table->string('nama_pengeluaran');
            $table->text('catatan_pengeluaran');
            $table->string('jumlah_pengeluaran');
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
        Schema::dropIfExists('pengeluaran_tokos');
    }
};
