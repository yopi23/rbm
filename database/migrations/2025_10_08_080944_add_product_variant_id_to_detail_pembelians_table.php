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
        Schema::table('detail_pembelians', function (Blueprint $table) {
            $table->foreignId('product_variant_id')
                  ->nullable() // Dibuat nullable karena hanya diisi saat finalize
                  ->after('sparepart_id')
                  ->constrained('product_variants') // Terhubung ke tabel product_variants
                  ->onDelete('set null'); // Jika varian dihapus, histori pembelian tidak ikut hilang
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_pembelians', function (Blueprint $table) {
           // Hapus foreign key constraint terlebih dahulu sebelum drop kolom
            $table->dropForeign(['product_variant_id']);
            $table->dropColumn('product_variant_id');
        });
    }
};
