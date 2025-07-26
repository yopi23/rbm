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
       Schema::table('detail_pembelians', function (Blueprint $table) {
           $table->integer('harga_khusus_toko')->after('harga_pasang')->nullable();
           $table->integer('harga_khusus_satuan')->after('harga_khusus_toko')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('detail_pembelians', function (Blueprint $table) {
            $table->dropColumn('harga_khusus_toko');
            $table->dropColumn('harga_khusus_satuan');
        });
    }
};
