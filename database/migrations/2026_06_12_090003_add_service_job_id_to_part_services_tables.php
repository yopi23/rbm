<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('detail_part_services', function (Blueprint $table) {
            $table->unsignedBigInteger('service_job_id')->nullable()->after('kode_sparepart');
            $table->foreign('service_job_id')->references('id')->on('service_jobs')->onDelete('set null');
        });

        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->unsignedBigInteger('service_job_id')->nullable()->after('nama_part');
            $table->foreign('service_job_id')->references('id')->on('service_jobs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('detail_part_services', function (Blueprint $table) {
            $table->dropForeign(['service_job_id']);
            $table->dropColumn('service_job_id');
        });

        Schema::table('detail_part_luar_services', function (Blueprint $table) {
            $table->dropForeign(['service_job_id']);
            $table->dropColumn('service_job_id');
        });
    }
};
