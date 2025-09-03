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
        Schema::create('asets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_owner');
            $table->string('nama_aset');
            $table->string('kategori_aset')->nullable(); // Contoh: Elektronik, Furnitur, Peralatan
            $table->date('tanggal_perolehan');
            $table->decimal('nilai_perolehan', 15, 2); // Harga beli aset
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asets');
    }
};
