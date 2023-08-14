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
        Schema::create('detail_part_services', function (Blueprint $table) {
            $table->id();
            $table->string('kode_services');
            $table->string('kode_sparepart');
            $table->string('qty_part');
            $table->string('detail_modal_part_service');
            $table->string('detail_harga_part_service');
            $table->string('user_input');
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
        Schema::dropIfExists('detail_part_services');
    }
};
