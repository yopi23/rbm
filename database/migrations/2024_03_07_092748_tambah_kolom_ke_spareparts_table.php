<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('spareparts', function (Blueprint $table) {
            $table->string('harga_ecer', 255)->nullable()->after('harga_jual');
            $table->text('kode_spl')->nullable()->after('kode_owner');
            //
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('spareparts', function (Blueprint $table) {
            $table->dropColumn('harga_ecer');
            $table->dropColumn('kode_spl');
        });
    }
};
