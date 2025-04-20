<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_tables', function (Blueprint $table) {
            $table->id();
            $table->string('kode_toko');
            $table->string('nama_pelanggan');
            $table->string('nama_toko')->nullable();
            $table->string('alamat_toko')->nullable();
            $table->string('status_toko')->default('-');
            $table->bigInteger('nomor_toko');
            $table->integer('kode_owner');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_tables');
    }
};
