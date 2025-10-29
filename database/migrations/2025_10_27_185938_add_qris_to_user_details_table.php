<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambah kolom QRIS dan Macrodroid Secret ke tabel user_details
     */
    public function up(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            // Data QRIS milik owner
            $table->text('qris_payload')->nullable()->after('saldo');
            // bisa diisi payload QRIS (kode base64, raw text, atau link image)

            $table->string('qris_display_name', 100)->nullable()->after('qris_payload');
            // nama merchant QRIS, opsional untuk ditampilkan di aplikasi

            $table->string('macrodroid_secret', 64)->nullable()->after('qris_display_name');
            // secret unik untuk webhook Macrodroid tiap owner
        });
    }

    /**
     * Rollback perubahan
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['qris_payload', 'qris_display_name', 'macrodroid_secret']);
        });
    }
};
