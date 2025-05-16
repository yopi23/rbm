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
        Schema::create('sub_kategori_spareparts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kategori_id'); // Reference to the parent category
            $table->string('nama_sub_kategori');
            $table->string('foto_sub_kategori')->default('-'); // Using the same pattern as kategori_spareparts
            $table->string('kode_owner');
            $table->timestamps();

            // Foreign key constraint
            $table->foreign('kategori_id')->references('id')->on('kategori_spareparts')->onDelete('cascade');
        });

        // Add a field to spareparts table to reference subcategory
        Schema::table('spareparts', function (Blueprint $table) {
            $table->unsignedBigInteger('kode_sub_kategori')->nullable()->after('kode_kategori');
            $table->foreign('kode_sub_kategori')->references('id')->on('sub_kategori_spareparts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // First remove the foreign key on spareparts table
        Schema::table('spareparts', function (Blueprint $table) {
            $table->dropForeign(['kode_sub_kategori']);
            $table->dropColumn('kode_sub_kategori');
        });

        // Then drop the subcategories table
        Schema::dropIfExists('sub_kategori_spareparts');
    }
};
