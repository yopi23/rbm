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
        Schema::create('stock_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sparepart_id');
            $table->integer('current_stock');
            $table->integer('reorder_point');
            $table->integer('reorder_quantity');
            $table->enum('status', ['pending', 'processed', 'canceled'])->default('pending');
            $table->string('created_by');
            $table->string('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sparepart_id')->references('id')->on('spareparts');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('stock_notifications');
    }
};
