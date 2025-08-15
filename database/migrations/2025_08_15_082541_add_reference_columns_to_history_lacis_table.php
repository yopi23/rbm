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
        Schema::table('history_laci', function (Blueprint $table) {
            // Tambah kolom untuk reference tracking
            $table->string('reference_type')->nullable()->after('keterangan'); // 'penarikan', 'penjualan', 'pembelian', etc
            $table->unsignedBigInteger('reference_id')->nullable()->after('reference_type'); // ID dari tabel terkait
            $table->string('reference_code')->nullable()->after('reference_id'); // Kode transaksi (backup)

            // Index untuk performance
            $table->index(['reference_type', 'reference_id']);
            $table->index(['reference_code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('history_laci', function (Blueprint $table) {
            // Drop index
            $table->dropIndex(['reference_type', 'reference_id']);
            $table->dropIndex(['reference_code']);

            // Drop columns
            $table->dropColumn([
                'reference_type',
                'reference_id',
                'reference_code'
            ]);
        });
    }
};
