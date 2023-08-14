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
        Schema::create('pengambilans', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pengambilan');
            $table->string('tgl_pengambilan');
            $table->string('nama_pengambilan');
            $table->string('total_bayar');
            $table->string('user_input');
            $table->string('status_pengambilan');
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
        Schema::dropIfExists('pengambilans');
    }
};
