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
        Schema::create('pengeluaran_operasionals', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_pengeluaran');
            $table->string('nama_pengeluaran');
            $table->string('kategori');
            $table->string('kode_pegawai');
            $table->string('jml_pengeluaran');
            $table->text('desc_pengeluaran');
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
        Schema::dropIfExists('pengeluaran_operasionals');
    }
};
