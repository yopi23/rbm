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
        Schema::create('garansis', function (Blueprint $table) {
            $table->id();
            $table->string('type_garansi');
            $table->string('kode_garansi');
            $table->string('nama_garansi');
            $table->string('tgl_mulai_garansi');
            $table->string('tgl_exp_garansi');
            $table->text('catatan_garansi');
            $table->string('status_garansi')->default('0');
            $table->string('user_input');
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
        Schema::dropIfExists('garansis');
    }
};
