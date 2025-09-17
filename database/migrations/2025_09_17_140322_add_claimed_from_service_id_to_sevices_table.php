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
        // Fungsi ini akan dijalankan saat Anda 'php artisan migrate'
        Schema::table('sevices', function (Blueprint $table) {
            // Menambahkan kolom baru 'claimed_from_service_id'
            $table->unsignedBigInteger('claimed_from_service_id')->nullable()->after('kode_owner');

            // Menambahkan foreign key constraint ke kolom tersebut
            // Ini menghubungkan ke kolom 'id' di tabel 'sevices' itu sendiri
            $table->foreign('claimed_from_service_id')
                  ->references('id')
                  ->on('sevices')
                  ->onDelete('set null'); // Jika service asli dihapus, kolom ini jadi NULL
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Fungsi ini akan dijalankan jika Anda 'php artisan migrate:rollback'
        Schema::table('sevices', function (Blueprint $table) {
            // Hapus foreign key terlebih dahulu sebelum menghapus kolomnya
            $table->dropForeign(['claimed_from_service_id']);

            // Hapus kolom 'claimed_from_service_id'
            $table->dropColumn('claimed_from_service_id');
        });
    }
};
