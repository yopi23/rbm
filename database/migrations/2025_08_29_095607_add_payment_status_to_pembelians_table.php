<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->string('metode_pembayaran')->default('Lunas')->after('status');
            $table->string('status_pembayaran')->default('Lunas')->after('metode_pembayaran');
            $table->date('tgl_jatuh_tempo')->nullable()->after('status_pembayaran');
        });
    }
    public function down(): void {
        Schema::table('pembelians', function (Blueprint $table) {
            $table->dropColumn(['metode_pembayaran', 'status_pembayaran', 'tgl_jatuh_tempo']);
        });
    }
};
