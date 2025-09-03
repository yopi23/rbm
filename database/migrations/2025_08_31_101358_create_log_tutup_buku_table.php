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
        Schema::create('log_tutup_buku', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_owner');
            $table->date('tanggal_operasional');
            $table->decimal('total_debit', 15, 2);
            $table->decimal('total_kredit', 15, 2);
            $table->unsignedBigInteger('user_id'); // Siapa yang melakukan tutup buku
            $table->timestamps();
            $table->unique(['kode_owner', 'tanggal_operasional']); // Hanya bisa tutup buku sekali sehari
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_tutup_buku');
    }
};
