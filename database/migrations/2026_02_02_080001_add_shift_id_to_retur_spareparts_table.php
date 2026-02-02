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
        Schema::table('retur_spareparts', function (Blueprint $table) {
            $table->unsignedBigInteger('shift_id')->nullable()->after('kode_owner');
            $table->index('shift_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retur_spareparts', function (Blueprint $table) {
            $table->dropColumn('shift_id');
        });
    }
};
