<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddStatusTitipanToPemasukanLainTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->enum('status_titipan', ['aktif', 'dicairkan'])->default('aktif')->after('sifat_pemasukan');
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
            $table->dropColumn('status_titipan');
        });
    }
}
