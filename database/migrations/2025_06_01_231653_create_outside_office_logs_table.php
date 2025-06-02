<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. CREATE: Tabel outside_office_logs (tabel baru)
        Schema::create('outside_office_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('log_date');
            $table->datetime('start_time');
            $table->datetime('end_time')->nullable();
            $table->datetime('actual_return_time')->nullable(); // Waktu aktual kembali
            $table->text('reason'); // Alasan keluar
            $table->enum('status', ['active', 'completed', 'violated'])->default('active');
            $table->integer('late_return_minutes')->default(0); // Terlambat kembali (menit)
            $table->enum('approval_status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('created_by');
            $table->text('violation_note')->nullable(); // Catatan pelanggaran
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index(['user_id', 'log_date']);
            $table->index(['status']);
        });

        // 2. UPDATE: Tabel user_details - tambah kolom untuk link ke current log
        Schema::table('user_details', function (Blueprint $table) {
            // Cek apakah kolom outside_start_time dan outside_end_time ada
            if (Schema::hasColumn('user_details', 'outside_start_time')) {
                $table->dropColumn('outside_start_time');
            }
            if (Schema::hasColumn('user_details', 'outside_end_time')) {
                $table->dropColumn('outside_end_time');
            }

            // Tambah foreign key ke outside_office_logs untuk tracking current active log
            $table->unsignedBigInteger('current_outside_log_id')->nullable()->after('outside_note');
            $table->foreign('current_outside_log_id')->references('id')->on('outside_office_logs')->onDelete('set null');
        });

        // 3. UPDATE: Tabel employee_monthly_reports - tambah kolom outside office data
        Schema::table('employee_monthly_reports', function (Blueprint $table) {
            $table->integer('outside_office_violations')->default(0)->after('total_late_minutes');
            $table->decimal('outside_office_penalties', 10, 2)->default(0)->after('outside_office_violations');
            $table->text('outside_office_summary')->nullable()->after('outside_office_penalties');
        });

        // 4. UPDATE: Tabel violations - tambah referensi ke outside office log
        Schema::table('violations', function (Blueprint $table) {
            $table->unsignedBigInteger('outside_office_log_id')->nullable()->after('description');
            $table->foreign('outside_office_log_id')->references('id')->on('outside_office_logs')->onDelete('set null');
        });
    }

    public function down()
    {
        // Hapus foreign keys dan kolom dari tabel yang diupdate
        Schema::table('violations', function (Blueprint $table) {
            $table->dropForeign(['outside_office_log_id']);
            $table->dropColumn('outside_office_log_id');
        });

        Schema::table('employee_monthly_reports', function (Blueprint $table) {
            $table->dropColumn(['outside_office_violations', 'outside_office_penalties', 'outside_office_summary']);
        });

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropForeign(['current_outside_log_id']);
            $table->dropColumn('current_outside_log_id');

            // Restore kolom lama jika diperlukan
            $table->datetime('outside_start_time')->nullable();
            $table->datetime('outside_end_time')->nullable();
        });

        // Drop tabel outside_office_logs
        Schema::dropIfExists('outside_office_logs');
    }
};
