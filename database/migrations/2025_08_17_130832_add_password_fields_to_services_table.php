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
        Schema::table('sevices', function (Blueprint $table) {
            // Tambahkan dua kolom baru ini
            $table->string('tipe_sandi')->nullable()->after('keterangan');
            $table->text('isi_sandi')->nullable()->after('tipe_sandi');
            $table->json('data_unit')->nullable()->after('isi_sandi');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sevices', function (Blueprint $table) {
            // Untuk menghapus kolom jika migrasi di-rollback
            $table->dropColumn(['tipe_sandi', 'isi_sandi','data_unit']);
        });
    }
};
