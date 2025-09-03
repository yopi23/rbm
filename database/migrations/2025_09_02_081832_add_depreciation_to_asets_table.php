<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('asets', function (Blueprint $table) {
            $table->integer('masa_manfaat_bulan')->default(48)->after('nilai_perolehan'); // Masa manfaat dalam bulan (default 4 tahun)
            $table->decimal('nilai_residu', 15, 2)->default(0)->after('masa_manfaat_bulan'); // Nilai sisa aset di akhir masa manfaat
            $table->decimal('penyusutan_terakumulasi', 15, 2)->default(0)->after('nilai_residu');
            $table->decimal('nilai_buku', 15, 2)->default(0)->after('penyusutan_terakumulasi');
        });
    }
    public function down(): void {
        Schema::table('asets', function (Blueprint $table) {
            $table->dropColumn(['masa_manfaat_bulan', 'nilai_residu', 'penyusutan_terakumulasi', 'nilai_buku']);
        });
    }
};
