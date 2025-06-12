<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('violations', function (Blueprint $table) {
            $table->decimal('applied_penalty_amount', 15, 2)->nullable()->after('penalty_percentage');
            $table->timestamp('processed_at')->nullable()->after('status');
            $table->unsignedBigInteger('processed_by')->nullable()->after('processed_at');
            $table->timestamp('applied_at')->nullable()->after('processed_by');
            $table->text('reversal_reason')->nullable()->after('applied_at');
            $table->timestamp('reversed_at')->nullable()->after('reversal_reason');
            $table->unsignedBigInteger('reversed_by')->nullable()->after('reversed_at');

            $table->foreign('processed_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('reversed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
       Schema::table('violations', function (Blueprint $table) {
            $table->dropForeign(['processed_by']);
            $table->dropForeign(['reversed_by']);
            $table->dropColumn([
                'applied_penalty_amount',
                'processed_at',
                'processed_by',
                'applied_at',
                'reversal_reason',
                'reversed_at',
                'reversed_by'
            ]);
        });
    }
};
