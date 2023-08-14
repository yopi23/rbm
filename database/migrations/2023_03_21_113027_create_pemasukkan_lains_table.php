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
        Schema::create('pemasukkan_lains', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_pemasukkan');
            $table->string('judul_pemasukan');
            $table->text('catatan_pemasukkan');
            $table->string('jumlah_pemasukkan');
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
        Schema::dropIfExists('pemasukkan_lains');
    }
};
