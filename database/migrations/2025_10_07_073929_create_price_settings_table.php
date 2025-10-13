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
        Schema::create('price_settings', function (Blueprint $table) {
        $table->id();

        // Relasi ke kategori sparepart (misal: LCD, baterai, aksesoris)
        $table->foreignId('kategori_sparepart_id')
            ->constrained('kategori_spareparts')
            ->onDelete('cascade');

        // Relasi opsional ke tabel kualitas / atribut (misal: OG, OLED, Meetoo, Spy Ceramic, dll)
        $table->foreignId('attribute_value_id')
            ->nullable()
            ->constrained('attribute_values')
            ->onDelete('cascade');

        // Margin harga (dalam persentase)
        $table->decimal('wholesale_margin', 5, 2)->default(0)->comment('Margin Grosir (%)');
        $table->decimal('retail_margin', 5, 2)->default(0)->comment('Margin Ecer (%)');
        $table->decimal('internal_margin', 5, 2)->default(0)->comment('Margin Internal (%)');

        // Biaya jasa tambahan untuk servis internal
        $table->unsignedInteger('default_service_fee')->nullable()->comment('Biaya jasa tambahan (Rp)');

        // Persentase tambahan opsional untuk garansi (jika suatu item punya garansi)
        $table->decimal('warranty_percentage', 5, 2)->nullable()->comment('Tambahan biaya garansi (%)');

        $table->timestamps();

        // Opsional: Unik kombinasi kategori + atribut (biar gak dobel entri)
        $table->unique(['kategori_sparepart_id', 'attribute_value_id'], 'unique_kategori_attr');
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('price_settings');
    }
};
