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
        Schema::create('kas_perusahaan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sourceable_id')->nullable();
            $table->string('sourceable_type')->nullable();

            // PENAMBAHAN WAJIB: Menambahkan kode_owner
            $table->unsignedBigInteger('kode_owner');

            $table->dateTime('tanggal');
            $table->string('deskripsi', 255);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->decimal('saldo', 15, 2)->default(0);
            $table->timestamps();

            // Foreign key constraint untuk kode_owner
            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
            $table->index(['sourceable_id', 'sourceable_type']);
        });

        Schema::create('transaksi_modal', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');

            // SUDAH ADA: kode_owner untuk mengikat transaksi modal ke pemilik
            $table->unsignedBigInteger('kode_owner');

            $table->string('jenis_transaksi'); // setoran_awal, tambahan_modal, penarikan_modal (prive)
            $table->decimal('jumlah', 15, 2);
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('close_book_setting', function (Blueprint $table) {
            $table->id();
            $table->time('jam')->nullable();
            $table->string('keterangan')->nullable();

            // SUDAH ADA: kode_owner diubah menjadi unsignedBigInteger untuk konsistensi
            $table->unsignedBigInteger('kode_owner');

            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('distribusi_setting', function (Blueprint $table) {
            $table->id();
            $table->string('role');
            $table->decimal('persentase', 5, 2);
            $table->string('keterangan')->nullable();

            // SUDAH ADA: kode_owner diubah menjadi unsignedBigInteger untuk konsistensi
            $table->unsignedBigInteger('kode_owner');

            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::create('distribusi_laba', function (Blueprint $table) {
            $table->id();
            $table->decimal('laba_kotor', 15, 2)->default(0);
            $table->decimal('laba_bersih', 15, 2)->default(0);
            $table->decimal('alokasi_owner', 15, 2)->default(0);
            $table->decimal('alokasi_investor', 15, 2)->default(0);
            $table->decimal('alokasi_karyawan', 15, 2)->default(0);
            $table->decimal('alokasi_kas_aset', 15, 2)->default(0);

            // SUDAH ADA: kode_owner diubah menjadi unsignedBigInteger untuk konsistensi
            $table->unsignedBigInteger('kode_owner');

            $table->dateTime('tanggal');
            $table->timestamps();

            $table->foreign('kode_owner')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('distribusi_laba');
        Schema::dropIfExists('distribusi_setting');
        Schema::dropIfExists('close_book_setting');
        Schema::dropIfExists('transaksi_modal');
        Schema::dropIfExists('kas_perusahaan');
    }
};
