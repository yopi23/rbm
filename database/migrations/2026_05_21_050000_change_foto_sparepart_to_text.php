<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Mengubah kolom foto_sparepart dari VARCHAR(255) ke TEXT
     * agar bisa menyimpan JSON array berisi banyak path foto (maks 5).
     */
    public function up()
    {
        // Use raw SQL to avoid Doctrine DBAL dependency issues
        DB::statement('ALTER TABLE spareparts MODIFY foto_sparepart TEXT');
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::statement('ALTER TABLE spareparts MODIFY foto_sparepart VARCHAR(255)');
    }
};
