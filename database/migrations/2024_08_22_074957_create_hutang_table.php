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
        Schema::create('hutang', function (Blueprint $table) {
            $table->id();
            $table->string('kode_supplier'); // Menyimpan kode supplier
            $table->integer('kode_owner'); // Menyimpan kode supplier
            $table->string('kode_nota'); // Menyimpan kode supplier
            $table->integer('total_hutang'); // Menyimpan total hutang dengan presisi
            $table->integer('status'); // Menyimpan total hutang dengan presisi
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
        Schema::dropIfExists('hutang');
    }
};
