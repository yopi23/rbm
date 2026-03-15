<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('kas_perusahaan', function (Blueprint $table) {
            $table->boolean('is_cash')->default(true)->after('saldo')
                ->comment('true = transaksi cash (masuk/keluar laci), false = transfer/non-cash');
        });
    }

    public function down()
    {
        Schema::table('kas_perusahaan', function (Blueprint $table) {
            $table->dropColumn('is_cash');
        });
    }
};
