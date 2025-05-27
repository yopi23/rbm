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
        Schema::create('qr_code_attendances', function (Blueprint $table) {
            $table->id();
            $table->string('token')->unique();
            $table->date('date');
            $table->enum('type', ['check_in', 'check_out'])->default('check_in');
            $table->unsignedBigInteger('created_by');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('qr_code_attendances');
    }
};
