<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('spareparts', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kode_owner');
            $table->foreign('cabang_id')->references('id')->on('cabangs')->onDelete('set null');
        });

        Schema::table('sevices', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kode_owner');
            $table->foreign('cabang_id')->references('id')->on('cabangs')->onDelete('set null');
        });

        Schema::table('penjualans', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kode_owner');
            $table->foreign('cabang_id')->references('id')->on('cabangs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('penjualans', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });

        Schema::table('sevices', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });

        Schema::table('spareparts', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
