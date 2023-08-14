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
        Schema::create('detail_catatan_services', function (Blueprint $table) {
            $table->id();
            $table->string('tgl_catatan_service');
            $table->string('kode_services');
            $table->string('kode_user');
            $table->text('catatan_service');
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
        Schema::dropIfExists('detail_catatan_services');
    }
};
