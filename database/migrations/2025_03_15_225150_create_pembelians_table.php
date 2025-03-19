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
        Schema::create('pembelians', function (Blueprint $table) {
            $table->id();
            $table->string('kode_pembelian')->unique();
            $table->date('tanggal_pembelian');
            $table->string('supplier')->nullable();
            $table->text('keterangan')->nullable();
            $table->integer('total_harga')->default(0);
            $table->enum('status', ['draft', 'selesai'])->default('draft');
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
        Schema::dropIfExists('pembelians');
    }
};
