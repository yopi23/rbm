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
        Schema::table('user_details', function (Blueprint $table) {
            // Tambah kolom face_registered_at jika belum ada
            if (!Schema::hasColumn('user_details', 'face_registered_at')) {
                $table->timestamp('face_registered_at')->nullable()->after('face_embedding');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            if (Schema::hasColumn('user_details', 'face_registered_at')) {
                $table->dropColumn('face_registered_at');
            }
        });
    }
};
