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
        // Tabel Periode Stock Opname
        Schema::create('stock_opname_periods', function (Blueprint $table) {
            $table->id();
            $table->string('kode_periode')->unique();
            $table->string('nama_periode');
            $table->date('tanggal_mulai');
            $table->date('tanggal_selesai');
            $table->enum('status', ['draft', 'in_progress', 'completed', 'cancelled'])->default('draft');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('user_input');
            $table->unsignedBigInteger('kode_owner');
            $table->timestamps();
            $table->softDeletes();

            // Foreign key ke user
            $table->foreign('user_input')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });

        // Tabel Detail Stock Opname
        Schema::create('stock_opname_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('period_id');
            $table->unsignedBigInteger('sparepart_id');
            $table->integer('stock_tercatat'); // Stok di sistem
            $table->integer('stock_aktual')->nullable(); // Stok hasil penghitungan fisik
            $table->integer('selisih')->nullable(); // Selisih antara stock_tercatat dan stock_aktual
            $table->enum('status', ['pending', 'checked', 'adjusted'])->default('pending');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('user_check')->nullable(); // User yang melakukan pengecekan
            $table->timestamp('checked_at')->nullable();
            $table->unsignedBigInteger('kode_owner');
            $table->timestamps();

            // Foreign keys
            $table->foreign('period_id')
                  ->references('id')
                  ->on('stock_opname_periods')
                  ->onDelete('cascade');

            $table->foreign('sparepart_id')
                  ->references('id')
                  ->on('spareparts')
                  ->onDelete('cascade');

            $table->foreign('user_check')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');

            // Unique constraint untuk memastikan tidak ada duplikasi sparepart dalam satu periode
            $table->unique(['period_id', 'sparepart_id']);
        });

        // Tabel Riwayat Penyesuaian Stock Opname
        Schema::create('stock_opname_adjustments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('detail_id');
            $table->integer('stock_before');
            $table->integer('stock_after');
            $table->integer('adjustment_qty'); // Jumlah penyesuaian (positif atau negatif)
            $table->text('alasan_adjustment');
            $table->unsignedBigInteger('user_input');
            $table->unsignedBigInteger('kode_owner');
            $table->timestamps();

            // Foreign keys
            $table->foreign('detail_id')
                  ->references('id')
                  ->on('stock_opname_details')
                  ->onDelete('cascade');

            $table->foreign('user_input')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_adjustments');
        Schema::dropIfExists('stock_opname_details');
        Schema::dropIfExists('stock_opname_periods');
    }
};
