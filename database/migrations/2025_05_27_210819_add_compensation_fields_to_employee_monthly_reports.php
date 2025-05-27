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
            $table->enum('compensation_type', ['fixed', 'percentage'])->after('user_id');
            $table->decimal('basic_salary', 15, 2)->default(0)->after('compensation_type');
            $table->decimal('total_part_cost', 15, 2)->default(0)->after('total_service_amount');
            $table->decimal('percentage_used', 5, 2)->nullable()->after('total_part_cost');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
         Schema::table('employee_monthly_reports', function (Blueprint $table) {
            $table->dropColumn(['compensation_type', 'basic_salary', 'total_part_cost', 'percentage_used']);
        });
    }
};
