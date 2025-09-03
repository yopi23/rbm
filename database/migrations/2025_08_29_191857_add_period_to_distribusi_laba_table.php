<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('distribusi_laba', function (Blueprint $table) {
            $table->date('tanggal_mulai')->after('tanggal');
            $table->date('tanggal_selesai')->after('tanggal_mulai');
        });
    }
    public function down(): void {
        Schema::table('distribusi_laba', function (Blueprint $table) {
            $table->dropColumn(['tanggal_mulai', 'tanggal_selesai']);
        });
    }
};
