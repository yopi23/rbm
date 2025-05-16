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

        // Langkah 1: Ubah nama kolom lama untuk menghindari konflik
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->renameColumn('brand_id', 'old_brand_id');
            $table->renameColumn('screen_size_id', 'old_screen_size_id');
            $table->renameColumn('camera_position_id', 'old_camera_position_id');
        });

        // Langkah 2: Tambahkan kolom foreign key baru
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->foreignId('brand_id')->nullable()->constrained();
            $table->foreignId('screen_size_id')->nullable()->constrained();
            $table->foreignId('camera_position_id')->nullable()->constrained();
        });

        // Langkah 3: Hapus kolom lama setelah selesai migrasi data (ini opsional)
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->dropColumn(['old_brand_id', 'old_screen_size_id', 'old_camera_position_id']);
        });
    }

    public function down(): void
    {
        // Langkah 1: Tambahkan kembali kolom lama
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->string('old_brand_id')->nullable();
            $table->string('old_screen_size_id')->nullable();
            $table->string('old_camera_position_id')->nullable();
        });

        // Langkah 2: Hapus foreign key constraints dan kolom
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->dropForeign(['brand_id']);
            $table->dropForeign(['screen_size_id']);
            $table->dropForeign(['camera_position_id']);
            $table->dropColumn(['brand_id', 'screen_size_id', 'camera_position_id']);
        });

        // Langkah 3: Kembalikan nama kolom lama
        Schema::table('hp_datas', function (Blueprint $table) {
            $table->renameColumn('old_brand_id', 'brand_id');
            $table->renameColumn('old_screen_size_id', 'screen_size_id');
            $table->renameColumn('old_camera_position_id', 'camera_position_id');
        });

        Schema::dropIfExists('camera_positions');
        Schema::dropIfExists('screen_sizes');
        Schema::dropIfExists('brands');
    }
};
