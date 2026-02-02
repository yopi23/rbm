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
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
        });

        Schema::table('stock_opname_adjustments', function (Blueprint $table) {
            $table->foreignId('shift_id')->nullable()->constrained('shifts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_opname_details', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });

        Schema::table('stock_opname_adjustments', function (Blueprint $table) {
            $table->dropForeign(['shift_id']);
            $table->dropColumn('shift_id');
        });
    }
};
