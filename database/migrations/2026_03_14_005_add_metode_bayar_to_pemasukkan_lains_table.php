<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->string('metode_bayar', 20)->default('cash')->after('jumlah_pemasukkan')
                ->comment('cash, transfer');
        });
    }

    public function down()
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->dropColumn('metode_bayar');
        });
    }
};
