<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('profit_presentases', function (Blueprint $table) {
            $table->boolean('is_cair')->default(false)->after('profit');
        });

        // Set all existing data to true as requested by the user
        DB::table('profit_presentases')->update(['is_cair' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('profit_presentases', function (Blueprint $table) {
            $table->dropColumn('is_cair');
        });
    }
};
