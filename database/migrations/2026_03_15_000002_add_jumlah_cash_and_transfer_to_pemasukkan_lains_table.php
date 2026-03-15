<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddJumlahCashAndTransferToPemasukkanLainsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->decimal('jumlah_cash', 15, 2)->default(0)->after('jumlah_pemasukkan');
            $table->decimal('jumlah_transfer', 15, 2)->default(0)->after('jumlah_cash');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->dropColumn(['jumlah_cash', 'jumlah_transfer']);
        });
    }
}