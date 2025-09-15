<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        Schema::table('beban_operasional', function (Blueprint $table) {
            if (!Schema::hasColumn('beban_operasional', 'periode')) {
                $table->string('periode')->default('bulanan')->after('nama_beban');
            }
        });

        // Rename kolom pakai SQL manual
        if (Schema::hasColumn('beban_operasional', 'jumlah_bulanan')) {
            DB::statement("ALTER TABLE beban_operasional CHANGE jumlah_bulanan nominal BIGINT NOT NULL");
        }
    }

    public function down()
    {
        Schema::table('beban_operasional', function (Blueprint $table) {
            if (Schema::hasColumn('beban_operasional', 'periode')) {
                $table->dropColumn('periode');
            }
        });

        // Rename balik pakai SQL manual
        if (Schema::hasColumn('beban_operasional', 'nominal')) {
            DB::statement("ALTER TABLE beban_operasional CHANGE nominal jumlah_bulanan BIGINT NOT NULL");
        }
    }
};
