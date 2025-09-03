<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('beban_operasional', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kode_owner');
            $table->string('nama_beban');
            $table->decimal('jumlah_bulanan', 15, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });
    }
    public function down(): void {
        Schema::dropIfExists('beban_operasional');
    }
};
