<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->string('metode_bayar', 20)->default('cash')->after('total_bayar')
                ->comment('cash, transfer, split');
            $table->bigInteger('jumlah_cash')->default(0)->after('metode_bayar');
            $table->bigInteger('jumlah_transfer')->default(0)->after('jumlah_cash');
        });
    }

    public function down()
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropColumn(['metode_bayar', 'jumlah_cash', 'jumlah_transfer']);
        });
    }
};
