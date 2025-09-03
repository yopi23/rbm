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
        Schema::create('alokasi_laba', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('distribusi_laba_id'); // Link ke log utama
            $table->unsignedBigInteger('kode_owner')->required();
            $table->unsignedBigInteger('user_id')->nullable(); // Untuk siapa alokasi ini (owner/investor/karyawan)
            $table->string('role'); // owner, investor, kas_aset, karyawan_bonus
            $table->decimal('jumlah', 15, 2);
            $table->string('status')->default('dialokasikan'); // Status: dialokasikan, ditarik
            $table->unsignedBigInteger('penarikan_id')->nullable(); // Link ke transaksi penarikan jika ada
            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
            // Menambahkan foreign key constraint ke tabel distribusi_laba
            $table->foreign('distribusi_laba_id')->references('id')->on('distribusi_laba')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alokasi_laba');
    }
};
