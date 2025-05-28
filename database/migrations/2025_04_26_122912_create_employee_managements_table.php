// database/migrations/2024_01_15_000001_create_employee_management_tables.php

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // Table for Attendance
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->enum('status', ['hadir', 'izin', 'sakit', 'alpha', 'libur', 'cuti']);
            $table->text('note')->nullable();
            $table->string('photo_in')->nullable(); // Foto saat check-in
            $table->string('photo_out')->nullable(); // Foto saat check-out
            $table->string('location')->nullable(); // Location coordinates
            $table->integer('late_minutes')->default(0);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });

        // Table for Salary Settings
        Schema::create('salary_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('compensation_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->integer('service_percentage')->default(0);
            $table->decimal('target_bonus', 12, 2)->default(0);
            $table->integer('monthly_target')->default(0);
            $table->decimal('percentage_value', 5, 2)->default(0);
            $table->foreignId('created_by')->constrained('users')->onDelete('restrict');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

        });

        // Table for Violations
        Schema::create('violations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('violation_date');
            $table->enum('type', ['telat', 'alpha', 'kelalaian', 'komplain', 'lainnya']);
            $table->text('description');
            $table->decimal('penalty_amount', 15, 2)->nullable();
            $table->integer('penalty_percentage')->nullable();
            $table->enum('status', ['pending', 'processed', 'forgiven'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        // Table for Employee Monthly Report
        Schema::create('employee_monthly_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->year('year');
            $table->tinyInteger('month');
            $table->integer('total_service_units')->default(0);
            $table->decimal('total_service_amount', 15, 2)->default(0);
            $table->decimal('total_commission', 15, 2)->default(0);
            $table->decimal('total_bonus', 15, 2)->default(0);
            $table->decimal('total_penalties', 15, 2)->default(0);
            $table->decimal('final_salary', 15, 2)->default(0);
            $table->integer('total_working_days')->default(0);
            $table->integer('total_present_days')->default(0);
            $table->integer('total_absent_days')->default(0);
            $table->integer('total_late_minutes')->default(0);
            $table->enum('status', ['draft', 'finalized', 'paid'])->default('draft');
            $table->unsignedBigInteger('processed_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
        });

        // Table for Work Schedules
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->enum('day_of_week', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday']);
            $table->time('start_time')->default('10:00:00');
            $table->time('end_time')->default('18:00:00');
            $table->boolean('is_working_day')->default(true);
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('restrict');
        });

        // Add new columns to existing user_details table
        Schema::table('user_details', function (Blueprint $table) {
            if (!Schema::hasColumn('user_details', 'is_outside_office')) {
                $table->boolean('is_outside_office')->default(false)->after('saldo');
            }
            if (!Schema::hasColumn('user_details', 'outside_note')) {
                $table->text('outside_note')->nullable()->after('is_outside_office');
            }
            if (!Schema::hasColumn('user_details', 'outside_start_time')) {
                $table->timestamp('outside_start_time')->nullable()->after('outside_note');
            }
            if (!Schema::hasColumn('user_details', 'outside_end_time')) {
                $table->timestamp('outside_end_time')->nullable()->after('outside_start_time');
            }
        });
    }

    public function down()
    {
        Schema::dropIfExists('employee_monthly_reports');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('salary_settings');
        Schema::dropIfExists('attendances');
        Schema::dropIfExists('work_schedules');

        Schema::table('user_details', function (Blueprint $table) {
            $table->dropColumn(['is_outside_office', 'outside_note', 'outside_start_time', 'outside_end_time']);
        });
    }
};
