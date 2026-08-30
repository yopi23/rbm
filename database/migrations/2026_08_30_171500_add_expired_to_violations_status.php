<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending', 'processed', 'forgiven', 'expired') DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE violations MODIFY COLUMN status ENUM('pending', 'processed', 'forgiven') DEFAULT 'pending'");
    }
};

