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
        Schema::table('penarikans', function (Blueprint $table) {
            // Tambah kolom untuk menandai penarikan oleh admin
            $table->boolean('admin_withdrawal')->default(false)->after('status_penarikan');

            // Tambah kolom untuk menyimpan ID admin yang melakukan penarikan
            $table->string('admin_id')->nullable()->after('admin_withdrawal');

            // Index untuk performa query
            $table->index(['admin_withdrawal', 'tgl_penarikan']);
            $table->index('admin_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->dropIndex(['admin_withdrawal', 'tgl_penarikan']);
            $table->dropIndex(['admin_id']);
            $table->dropColumn(['admin_withdrawal', 'admin_id']);
        });
    }
};
