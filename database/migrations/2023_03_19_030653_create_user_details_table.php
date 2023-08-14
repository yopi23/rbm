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
        Schema::create('user_details', function (Blueprint $table) {
            $table->id();
            $table->string('kode_user');
            $table->string('foto_user');
            $table->string('fullname');
            $table->string('alamat_user');
            $table->string('no_telp');
            $table->string('jabatan');
            $table->string('status_user');
            $table->string('id_upline')->nullable();
            $table->string('saldo')->default('0');
            $table->string('kode_invite');
            $table->string('link_twitter');
            $table->string('link_facebook');
            $table->string('link_instagram');
            $table->string('link_linkedin');
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
        Schema::dropIfExists('user_details');
    }
};
