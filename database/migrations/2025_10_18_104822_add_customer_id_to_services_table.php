<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCustomerIdToServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (Schema::hasTable('sevices')) {
            Schema::table('sevices', function (Blueprint $table) {
                // Tambahkan kolom customer_id yang bisa null
                $table->unsignedBigInteger('customer_id')->nullable()->after('id');

                // Tambahkan foreign key constraint ke tabel customer_tables
                // onDelete('set null') berarti jika pelanggan dihapus, data service tidak ikut terhapus
                $table->foreign('customer_id')
                      ->references('id')
                      ->on('customer_tables')
                      ->onDelete('set null');
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        if (Schema::hasTable('sevices')) {
            Schema::table('sevices', function (Blueprint $table) {
                // Hapus foreign key terlebih dahulu
                $table->dropForeign(['customer_id']);
                // Hapus kolomnya
                $table->dropColumn('customer_id');
            });
        }
    }
}
