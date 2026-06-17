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
        // 1. Create cabangs table
        Schema::create('cabangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_owner');
            $table->string('nama_cabang');
            $table->text('alamat_cabang')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // 2. Create jurnal_harian_cabangs table
        Schema::create('jurnal_harian_cabangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cabang_id');
            $table->unsignedBigInteger('shift_id')->nullable();
            $table->date('tanggal');
            $table->decimal('omset_tunai', 15, 2)->default(0);
            $table->decimal('omset_non_tunai', 15, 2)->default(0);
            $table->decimal('hpp_terjual', 15, 2)->default(0);
            $table->decimal('biaya_operasional_lokal', 15, 2)->default(0);
            $table->decimal('komisi_teknisi', 15, 2)->default(0);
            $table->decimal('kas_seharusnya_disetor', 15, 2)->default(0);
            $table->decimal('kas_aktual_disetor', 15, 2)->default(0);
            $table->timestamps();
        });

        // 3. Alter users table
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
        });

        // 4. Alter shifts table
        Schema::table('shifts', function (Blueprint $table) {
            $table->unsignedBigInteger('cabang_id')->nullable()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shifts', function (Blueprint $table) {
            $table->dropColumn('cabang_id');
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('cabang_id');
        });

        Schema::dropIfExists('jurnal_harian_cabangs');
        Schema::dropIfExists('cabangs');
    }
};
