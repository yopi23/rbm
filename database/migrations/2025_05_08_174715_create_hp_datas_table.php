<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hp_datas', function (Blueprint $table) {
            $table->id();
            $table->string('brand_id')->nullable();
            $table->string('screen_size_id')->nullable();
            $table->string('type')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('camera_position_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hp_datas');
    }
};
