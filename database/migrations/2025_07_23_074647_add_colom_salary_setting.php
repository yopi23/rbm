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
        Schema::table('salary_settings', function (Blueprint $table) {
            $table->decimal('max_salary',15, 2)->after('basic_salary');
            $table->decimal('max_percentage',15, 2)->after('percentage_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salary_settings', function (Blueprint $table) {
            $table->dropColumn(['max_salary']);
            $table->dropColumn(['max_percentage']);
        });
    }
};
