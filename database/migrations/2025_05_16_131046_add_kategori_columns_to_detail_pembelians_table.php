<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('detail_pembelians', function (Blueprint $table) {
            $table->unsignedBigInteger('item_kategori')->nullable()->after('is_new_item');
            $table->unsignedBigInteger('item_sub_kategori')->nullable()->after('item_kategori');

            $table->foreign('item_kategori', 'detail_pembelians_item_kategori_foreign')
                  ->references('id')
                  ->on('kategori_spareparts')
                  ->onDelete('set null');

            $table->foreign('item_sub_kategori', 'detail_pembelians_item_sub_kategori_foreign')
                  ->references('id')
                  ->on('sub_kategori_spareparts')
                  ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('detail_pembelians', function (Blueprint $table) {
            $table->dropForeign('detail_pembelians_item_kategori_foreign');
            $table->dropForeign('detail_pembelians_item_sub_kategori_foreign');

            $table->dropColumn('item_kategori');
            $table->dropColumn('item_sub_kategori');
        });
    }
};
