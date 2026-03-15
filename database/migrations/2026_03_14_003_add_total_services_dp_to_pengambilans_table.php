<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('pengambilans', function (Blueprint $table) {
            // Tambahkan kolom total_services dan dp yang belum ada
            if (!Schema::hasColumn('pengambilans', 'total_services')) {
                $table->bigInteger('total_services')->default(0)->after('total_bayar')
                    ->comment('Total harga semua services sebelum dipotong DP');
            }
            if (!Schema::hasColumn('pengambilans', 'dp')) {
                $table->bigInteger('dp')->default(0)->after('total_services')
                    ->comment('Jumlah DP yang sudah dibayar');
            }
            // Tambahkan juga kolom split payment jika belum ada (dari migration sebelumnya)
            if (!Schema::hasColumn('pengambilans', 'metode_bayar')) {
                $table->string('metode_bayar', 20)->default('cash')->after('dp')
                    ->comment('cash, transfer, split');
            }
            if (!Schema::hasColumn('pengambilans', 'jumlah_cash')) {
                $table->bigInteger('jumlah_cash')->default(0)->after('metode_bayar');
            }
            if (!Schema::hasColumn('pengambilans', 'jumlah_transfer')) {
                $table->bigInteger('jumlah_transfer')->default(0)->after('jumlah_cash');
            }
        });
    }

    public function down()
    {
        Schema::table('pengambilans', function (Blueprint $table) {
            $columns = ['total_services', 'dp'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('pengambilans', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
