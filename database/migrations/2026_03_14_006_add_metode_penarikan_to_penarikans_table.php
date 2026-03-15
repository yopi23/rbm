<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->string('metode_penarikan', 20)->default('cash')->after('jumlah_penarikan')
                ->comment('cash, transfer, bayar_hutang, split');
            $table->bigInteger('jumlah_cash')->default(0)->after('metode_penarikan');
            $table->bigInteger('jumlah_transfer')->default(0)->after('jumlah_cash');
            $table->bigInteger('jumlah_bayar_hutang')->default(0)->after('jumlah_transfer');
            $table->unsignedBigInteger('hutang_id')->nullable()->after('jumlah_bayar_hutang');

            $table->foreign('hutang_id')->references('id')->on('hutang')->nullOnDelete();
        });
    }

    public function down()
    {
        Schema::table('penarikans', function (Blueprint $table) {
            $table->dropForeign(['hutang_id']);
            $table->dropColumn(['metode_penarikan', 'jumlah_cash', 'jumlah_transfer', 'jumlah_bayar_hutang', 'hutang_id']);
        });
    }
};
