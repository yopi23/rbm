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
            $table->decimal('total_shop_profit', 15, 2)->default(0)->after('total_commission');
             // Jumlah klaim yang dikerjakan teknisi dari pekerjaan orang lain
            $table->integer('total_claims_handled')->default(0)->after('total_shop_profit');
            // Jumlah pekerjaan teknisi ini yang diklaim oleh orang lain
            $table->integer('claims_from_own_work')->default(0)->after('total_claims_handled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('employee_monthly_reports', function (Blueprint $table) {
            $table->dropColumn('total_shop_profit');
            $table->dropColumn(['claims_on_others_work', 'claims_from_own_work']);

        });
    }
};
