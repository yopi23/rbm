<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBebanOperasionalIdToPengeluaranOperasionalsTable extends Migration
{
    public function up()
    {
        Schema::table('pengeluaran_operasionals', function (Blueprint $table) {
            // Menambahkan foreign key ke tabel beban_operasionals
            // 'nullable' berarti tidak semua pengeluaran operasional harus terkait dengan beban tetap.
            // 'onDelete('set null')' berarti jika beban tetap dihapus, catatan pengeluaran tidak ikut terhapus.
            $table->foreignId('beban_operasional_id')
                  ->nullable()
                  ->after('id') // Opsi: meletakkan kolom setelah kolom id
                  ->constrained('beban_operasionals')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('pengeluaran_operasionals', function (Blueprint $table) {
            // Perintah untuk rollback (jika diperlukan)
            $table->dropForeign(['beban_operasional_id']);
            $table->dropColumn('beban_operasional_id');
        });
    }
}
