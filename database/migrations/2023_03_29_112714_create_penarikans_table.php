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
        Schema::create('penarikans', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_penarikan');
            $table->string('kode_penarikan');
            $table->string('kode_user');
            $table->string('kode_owner');
            $table->string('jumlah_penarikan');
            $table->string('catatan_penarikan');
            $table->string('status_penarikan');
            $table->string('dari_saldo');
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
        Schema::dropIfExists('penarikans');
    }
};
