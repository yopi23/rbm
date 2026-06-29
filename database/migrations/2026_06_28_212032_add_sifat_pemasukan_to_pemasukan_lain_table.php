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
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->enum('sifat_pemasukan', ['laba', 'pendapatan', 'titipan'])->default('laba')->after('jumlah_pemasukkan');
            $table->bigInteger('modal_pemasukan')->default(0)->after('sifat_pemasukan');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pemasukkan_lains', function (Blueprint $table) {
            $table->dropColumn(['sifat_pemasukan', 'modal_pemasukan']);
        });
    }
};
