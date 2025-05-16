<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
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

        Schema::table('hp_datas', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained()->after('id');
            $table->foreignId('screen_size_id')->nullable()->constrained()->after('brand_id');
            $table->foreignId('camera_position_id')->nullable()->constrained()->after('screen_size_id');
        });

        Schema::table('hp_datas', function (Blueprint $table) {
            $table->dropColumn(['brand', 'screen_size', 'camera_position']);
        });
    }

    public function down(): void
    {
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['screen_size_id']);
            $table->dropForeign(['camera_position_id']);
            $table->dropColumn(['brand_id', 'screen_size_id', 'camera_position_id']);
            $table->string('brand')->nullable();
            $table->string('screen_size')->nullable();
            $table->string('camera_position')->nullable();
        });

        Schema::dropIfExists('brands');
        Schema::dropIfExists('screen_sizes');
        Schema::dropIfExists('camera_positions');
    }
};
