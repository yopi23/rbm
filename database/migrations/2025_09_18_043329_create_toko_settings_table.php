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
        Schema::create('toko_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_owner'); // Merujuk ke id_upline di user_details
            $table->string('nama_toko')->nullable();
            $table->text('alamat_toko')->nullable();
            $table->string('nomor_cs')->nullable();
            $table->string('nomor_info_bot')->nullable();
            $table->text('nota_footer_line1')->nullable(); // Untuk "3 bulan tidak diambil..."
            $table->text('nota_footer_line2')->nullable(); // Untuk "Terimakasih!"
            $table->string('logo_url')->nullable(); // Path ke file logo
            $table->timestamps();

            $table->foreign('id_owner')->references('id')->on('users')->onDelete('cascade');
            $table->unique('id_owner'); // Pastikan setiap owner hanya punya 1 set pengaturan
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('toko_settings');
    }
};
