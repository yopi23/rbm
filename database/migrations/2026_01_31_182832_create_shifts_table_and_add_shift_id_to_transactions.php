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
        Schema::create('shifts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_owner');
            $table->unsignedBigInteger('user_id'); // Cashier/User who opened the shift
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();
            $table->decimal('modal_awal', 15, 2)->default(0); // Initial Cash in Drawer
            $table->decimal('saldo_akhir_sistem', 15, 2)->nullable(); // Calculated by System
            $table->decimal('saldo_akhir_aktual', 15, 2)->nullable(); // Input by User
            $table->decimal('selisih', 15, 2)->nullable(); // Diff
            $table->enum('status', ['open', 'closed'])->default('open');
            $table->text('note')->nullable();
            $table->json('report_data')->nullable(); // Snapshot of the report when closed
            $table->timestamps();
        });

        // Add shift_id to transactions
        $tables = [
            'penjualans',
            'sevices',
            'pengeluaran_tokos',
            'pengeluaran_operasionals',
            'pembelians',
            'kas_perusahaan'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (!Schema::hasColumn($tableName, 'shift_id')) {
                        $table->unsignedBigInteger('shift_id')->nullable()->after('id');
                        // Index for performance
                        $table->index('shift_id');
                    }
                });
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'penjualans',
            'sevices',
            'pengeluaran_tokos',
            'pengeluaran_operasionals',
            'pembelians',
            'kas_perusahaan'
        ];

        foreach ($tables as $tableName) {
            if (Schema::hasTable($tableName)) {
                Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                    if (Schema::hasColumn($tableName, 'shift_id')) {
                        $table->dropColumn('shift_id');
                    }
                });
            }
        }

        Schema::dropIfExists('shifts');
    }
};
