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
        // Alter compensation_type to VARCHAR to easily allow new compensation type 'tiered'
        DB::statement("ALTER TABLE salary_settings MODIFY COLUMN compensation_type VARCHAR(50) DEFAULT 'fixed'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE salary_settings MODIFY COLUMN compensation_type ENUM('fixed', 'percentage') DEFAULT 'fixed'");
    }
};
