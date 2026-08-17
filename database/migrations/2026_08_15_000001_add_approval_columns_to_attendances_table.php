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
        Schema::table('attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('attendances', 'approval_status')) {
                $table->enum('approval_status', ['approved', 'pending', 'rejected'])
                    ->default('approved')
                    ->after('status');
            }
            if (!Schema::hasColumn('attendances', 'rejection_reason')) {
                $table->text('rejection_reason')->nullable()->after('note');
            }
            if (!Schema::hasColumn('attendances', 'approved_by')) {
                $table->unsignedBigInteger('approved_by')->nullable()->after('created_by');
                $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            }
            if (!Schema::hasColumn('attendances', 'approved_at')) {
                $table->timestamp('approved_at')->nullable()->after('approved_by');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            if (Schema::hasColumn('attendances', 'approved_by')) {
                $table->dropForeign(['approved_by']);
                $table->dropColumn('approved_by');
            }
            if (Schema::hasColumn('attendances', 'approved_at')) {
                $table->dropColumn('approved_at');
            }
            if (Schema::hasColumn('attendances', 'rejection_reason')) {
                $table->dropColumn('rejection_reason');
            }
            if (Schema::hasColumn('attendances', 'approval_status')) {
                $table->dropColumn('approval_status');
            }
        });
    }
};
