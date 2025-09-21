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
        Schema::table('employee_monthly_reports', function (Blueprint $table) {
           $table->integer('completed_units_not_taken')->default(0)->after('total_service_units');
            $table->integer('taken_units')->default(0)->after('completed_units_not_taken');
            // Tambahkan kolom baru setelah 'total_shop_profit'
            $table->decimal('potential_shop_profit', 15, 2)->default(0)->after('total_shop_profit');
            $table->decimal('real_shop_profit', 15, 2)->default(0)->after('potential_shop_profit');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_monthly_reports', function (Blueprint $table) {
           $table->dropColumn(['completed_units_not_taken', 'taken_units', 'potential_shop_profit', 'real_shop_profit']);
        });
    }
};
