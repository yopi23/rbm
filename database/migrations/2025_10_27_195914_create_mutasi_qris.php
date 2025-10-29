<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_qris', function (Blueprint $table) {
            $table->id();

            // siapa pemilik QRIS / HP yang ada macrodroid
            $table->unsignedBigInteger('owner_detail_id');
            // -> ini ngarah ke user_details.id dari OWNER

            // siapa karyawan yang tadi lagi buka QRIS di kasir
            $table->unsignedBigInteger('kasir_detail_id')->nullable();
            // -> ini ngarah ke user_details.id dari KARYAWAN

            // nominal dana masuk
            $table->integer('nominal')->default(0);

            // catatan/desk dari bank, misal "QRIS payment" / "Tarik tunai"
            $table->string('keterangan', 191)->nullable();

            // penanda status notifikasi
            // new    = baru dicatat dari macrodroid
            // sent   = sudah dikirim (ditampilkan) ke HP kasir
            // read   = kasir sudah lihat di app
            $table->string('status', 20)->default('new');

            // timestamp masuknya dana menurut macrodroid/pemberitahuan
            $table->timestamp('reported_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_qris');
    }
};
