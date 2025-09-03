<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('hutang', function (Blueprint $table) {
            $table->date('tgl_jatuh_tempo')->nullable()->after('total_hutang');
        });
    }
    public function down(): void {
        Schema::table('hutang', function (Blueprint $table) {
            $table->dropColumn('tgl_jatuh_tempo');
        });
    }
};
