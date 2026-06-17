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
        Schema::create('service_categories', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->integer('persentase'); // e.g. 30, 40, 50
            $table->string('kode_warna')->default('#4CAF50');
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('kode_owner')->nullable(); // owner upline ID
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
        Schema::dropIfExists('service_categories');
    }
};
