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
        // 1. Tabel Pengaturan KPI & Bonus
        if (!Schema::hasTable('kpi_settings')) {
            Schema::create('kpi_settings', function (Blueprint $table) {
                $table->id();
                $table->string('kode_owner')->index();
                $table->decimal('weight_productivity', 5, 2)->default(30.00);
                $table->decimal('weight_quality', 5, 2)->default(30.00);
                $table->decimal('weight_rework', 5, 2)->default(20.00);
                $table->decimal('weight_discipline', 5, 2)->default(10.00);
                $table->decimal('weight_sop', 5, 2)->default(10.00);
                $table->string('bonus_scheme')->default('hybrid'); // 'pool', 'tiered', 'hybrid'
                $table->decimal('pool_percentage', 5, 2)->default(10.00); // 10% dari Profit Toko
                $table->decimal('min_kpi_bonus', 5, 2)->default(70.00); // Minimal KPI 70% untuk dapat bonus
                $table->json('tier_rules')->nullable(); // Aturan tiering bonus kustom
                $table->boolean('is_active')->default(true);
                $table->unsignedBigInteger('created_by')->nullable();
                $table->unsignedBigInteger('updated_by')->nullable();
                $table->timestamps();
            });
        }

        // 2. Kolom tambahan pada tabel sevices (SOP & Rework)
        if (Schema::hasTable('sevices')) {
            Schema::table('sevices', function (Blueprint $table) {
                if (!Schema::hasColumn('sevices', 'sop_checklist')) {
                    $table->json('sop_checklist')->nullable();
                }
                if (!Schema::hasColumn('sevices', 'is_rework')) {
                    $table->boolean('is_rework')->default(false);
                }
            });
        }

        // 3. Kolom skor KPI & ranking pada employee_monthly_reports
        if (Schema::hasTable('employee_monthly_reports')) {
            Schema::table('employee_monthly_reports', function (Blueprint $table) {
                if (!Schema::hasColumn('employee_monthly_reports', 'score_productivity')) {
                    $table->decimal('score_productivity', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'score_quality')) {
                    $table->decimal('score_quality', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'score_rework')) {
                    $table->decimal('score_rework', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'score_discipline')) {
                    $table->decimal('score_discipline', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'score_sop')) {
                    $table->decimal('score_sop', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'final_kpi_score')) {
                    $table->decimal('final_kpi_score', 5, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'kpi_rank')) {
                    $table->integer('kpi_rank')->nullable();
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'pool_bonus_share')) {
                    $table->decimal('pool_bonus_share', 15, 2)->default(0);
                }
                if (!Schema::hasColumn('employee_monthly_reports', 'kpi_metadata')) {
                    $table->json('kpi_metadata')->nullable();
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kpi_settings');

        Schema::table('sevices', function (Blueprint $table) {
            if (Schema::hasColumn('sevices', 'sop_checklist')) {
                $table->dropColumn('sop_checklist');
            }
            if (Schema::hasColumn('sevices', 'is_rework')) {
                $table->dropColumn('is_rework');
            }
        });

        Schema::table('employee_monthly_reports', function (Blueprint $table) {
            $cols = [
                'score_productivity',
                'score_quality',
                'score_rework',
                'score_discipline',
                'score_sop',
                'final_kpi_score',
                'kpi_rank',
                'pool_bonus_share',
                'kpi_metadata'
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('employee_monthly_reports', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
