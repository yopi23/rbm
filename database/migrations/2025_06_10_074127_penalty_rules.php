<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('penalty_rules', function (Blueprint $table) {
            $table->id();
            $table->string('rule_type')->index(); // 'attendance_late', 'outside_office_late', 'absence', etc.
            $table->string('compensation_type')->index(); // 'fixed', 'percentage', 'both'
            $table->integer('min_minutes')->default(0); // Minimum minutes for this rule
            $table->integer('max_minutes')->nullable(); // Maximum minutes (null = unlimited)
            $table->decimal('penalty_amount', 12, 2)->default(0); // For fixed salary (Rupiah)
            $table->integer('penalty_percentage')->default(0); // For percentage salary (%)
            $table->string('description'); // User-friendly description
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(1); // For ordering rules
            $table->integer('kode_owner')->nullable();
            $table->json('metadata')->nullable(); // Additional settings
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['rule_type', 'compensation_type', 'is_active']);
            $table->index(['min_minutes', 'max_minutes']);

            // Foreign keys
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('penalty_rules');
    }
};
