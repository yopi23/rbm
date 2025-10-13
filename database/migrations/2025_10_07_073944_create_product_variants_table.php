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
        Schema::create('product_variants', function (Blueprint $table) {
            $table->id();
            // Menggunakan nama tabel Anda: spareparts
            $table->foreignId('sparepart_id')->constrained('spareparts')->onDelete('cascade');
            $table->string('sku')->unique()->nullable();
            $table->unsignedInteger('purchase_price')->default(0)->comment('Harga Beli/Modal');
            $table->unsignedInteger('wholesale_price')->default(0)->comment('Harga Grosir (Hasil Kalkulasi)');
            $table->unsignedInteger('retail_price')->default(0)->comment('Harga Ecer (Hasil Kalkulasi)');
            $table->unsignedInteger('internal_price')->default(0)->comment('Harga Internal/Pasang (Hasil Kalkulasi)');
            $table->integer('stock')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variants');
    }
};
