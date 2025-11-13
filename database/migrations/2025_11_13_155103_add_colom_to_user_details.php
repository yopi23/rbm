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
            // Tracking kolom untuk face embedding
            $table->integer('face_embedding_count')->nullable()->after('face_embedding')
                ->comment('Jumlah foto yang digunakan saat enrollment');

            $table->timestamp('face_last_updated_at')->nullable()->after('face_registered_at')
                ->comment('Tanggal terakhir embedding di-update (auto-update)');

            $table->integer('face_verification_count')->default(0)->after('face_last_updated_at')
                ->comment('Total berapa kali berhasil verifikasi (untuk monitoring)');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn([
                'face_embedding_count',
                'face_registered_at',
                'face_last_updated_at',
                'face_verification_count'
            ]);
        });
    }
};
