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
        Schema::table('asets', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kode_owner');
            $table->foreign('cabang_id')->references('id')->on('cabangs')->onDelete('set null');
        });

        Schema::table('beban_operasional', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('kode_owner');
            $table->foreign('cabang_id')->references('id')->on('cabangs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('beban_operasional', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });

        Schema::table('asets', function (Blueprint $table) {
            $table->dropForeign(['cabang_id']);
            $table->dropColumn('cabang_id');
        });
    }
};
