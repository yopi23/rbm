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
        Schema::table('sevices', function (Blueprint $table) {
            $table->text('nama_sp')->nullable()->after('dp');
            $table->string('harga_sp')->default('0')->after('nama_sp');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('sevices', function (Blueprint $table) {
            Schema::dropColumn('nama_sp');
            Schema::dropColumn('harga_sp');
        });
    }
};
