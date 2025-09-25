<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salary_settings', function (Blueprint $table) {
            $table->integer('target_transaction_count')
                  ->nullable()
                  ->default(0)
                  ->after('target_shop_profit');

            $table->decimal('target_sales_revenue', 15, 2)
                  ->nullable()
                  ->default(0.00)
                  ->after('target_transaction_count');
        });
    }

    public function down(): void
    {
        Schema::table('salary_settings', function (Blueprint $table) {
            $table->dropColumn(['target_transaction_count', 'target_sales_revenue']);
        });
    }
};
