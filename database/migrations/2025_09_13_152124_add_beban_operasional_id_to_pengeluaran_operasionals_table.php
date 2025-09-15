<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('pengeluaran_operasionals', function (Blueprint $table) {
            if (!Schema::hasColumn('pengeluaran_operasionals', 'beban_operasional_id')) {
                $table->unsignedBigInteger('beban_operasional_id')->nullable()->after('id');
                $table->foreign('beban_operasional_id')
                      ->references('id')
                      ->on('beban_operasional')
                      ->onDelete('set null');
            }
        });
    }

    public function down()
    {
        Schema::table('pengeluaran_operasionals', function (Blueprint $table) {
            if (Schema::hasColumn('pengeluaran_operasionals', 'beban_operasional_id')) {
                $table->dropForeign(['beban_operasional_id']);
                $table->dropColumn('beban_operasional_id');
            }
        });
    }
};
