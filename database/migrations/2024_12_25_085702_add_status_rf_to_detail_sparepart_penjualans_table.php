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
        Schema::table('detail_sparepart_penjualans', function (Blueprint $table) {
            $table->integer('status_rf')->default(0)->after('user_input');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_sparepart_penjualans', function (Blueprint $table) {
            $table->dropColumn('status_rf');  // Hapus kolom `status_rf`
        });
    }
};
