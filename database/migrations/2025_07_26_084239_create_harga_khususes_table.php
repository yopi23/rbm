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
        Schema::create('harga_khususes', function (Blueprint $table) {
            $table->id();
            $table->integer('id_sp');
            $table->integer('harga_toko')->nullable();
            $table->integer('harga_satuan')->nullable();

            // Kolom tambahan untuk diskon
            $table->enum('diskon_tipe', ['persentase', 'potongan'])->nullable();
            $table->decimal('diskon_nilai', 10, 2)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('harga_khususes');
    }
};
