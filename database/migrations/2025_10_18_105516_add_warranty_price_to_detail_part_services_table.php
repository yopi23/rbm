<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWarrantyPriceToDetailPartServicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Pastikan tabel ada sebelum mencoba memodifikasinya
        if (Schema::hasTable('detail_part_services')) {
            Schema::table('detail_part_services', function (Blueprint $table) {
                // HANYA Tambahkan kolom untuk harga garansi setelah 'detail_harga_part_service'
                $table->bigInteger('harga_garansi')->default(0)->after('detail_harga_part_service');
                $table->bigInteger('jasa')->default(0)->after('harga_garansi');
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
        if (Schema::hasTable('detail_part_services')) {
            Schema::table('detail_part_services', function (Blueprint $table) {
                // Hapus kolom jika migrasi di-rollback
                $table->dropColumn('harga_garansi');
                $table->dropColumn('jasa');
            });
        }
    }
}
