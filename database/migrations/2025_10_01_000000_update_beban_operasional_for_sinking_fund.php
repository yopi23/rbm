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
        Schema::table('beban_operasional', function (Blueprint $table) {
            $table->decimal('current_balance', 15, 2)->default(0)->after('nominal');
            $table->boolean('is_active')->default(true)->after('current_balance');
            // Pastikan kolom periode ada (jika belum)
            if (!Schema::hasColumn('beban_operasional', 'periode')) {
                $table->enum('periode', ['bulanan', 'tahunan'])->default('bulanan')->after('nama_beban');
            }
        });

        // Update Aset untuk memastikan konsistensi jika ada yang null
        Schema::table('asets', function (Blueprint $table) {
             // Jika perlu index atau perubahan lain
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beban_operasional', function (Blueprint $table) {
            $table->dropColumn(['current_balance', 'is_active']);
            if (Schema::hasColumn('beban_operasional', 'periode')) {
                // $table->dropColumn('periode'); // Jangan drop jika sudah ada sebelumnya
            }
        });
    }
};
