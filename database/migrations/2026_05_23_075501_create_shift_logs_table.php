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
        Schema::create('shift_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('action_type'); // e.g. INPUT_SERVICE, WITHDRAWAL, SPAREPART_USAGE, PENJUALAN, DRAFT_PENJUALAN
            $table->text('description')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->string('related_type')->nullable();
            $table->decimal('amount', 15, 2)->nullable()->default(0); // for kas/komisi tracking
            $table->timestamps();
            
            // Add basic indexes
            $table->index('shift_id');
            $table->index('user_id');
            $table->index(['related_id', 'related_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shift_logs');
    }
};
