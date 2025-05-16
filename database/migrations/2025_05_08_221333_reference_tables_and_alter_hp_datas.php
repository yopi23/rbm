<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Hanya buat tabel referensi
        Schema::create('brands', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        Schema::create('screen_sizes', function (Blueprint $table) {
            $table->id();
            $table->string('size')->unique();
            $table->timestamps();
        });

        Schema::create('camera_positions', function (Blueprint $table) {
            $table->id();
            $table->string('position')->unique();
            $table->string('group')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('camera_positions');
        Schema::dropIfExists('screen_sizes');
        Schema::dropIfExists('brands');
    }
};
