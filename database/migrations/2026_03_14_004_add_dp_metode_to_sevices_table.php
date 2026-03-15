<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration 
{
    public function up()
    {
        Schema::table('sevices', function (Blueprint $table) {
            $table->string('dp_metode', 20)->default('cash')->after('dp')
                ->comment('cash, transfer, split');
            $table->bigInteger('dp_cash')->default(0)->after('dp_metode');
            $table->bigInteger('dp_transfer')->default(0)->after('dp_cash');
        });
    }

    public function down()
    {
        Schema::table('sevices', function (Blueprint $table) {
            $table->dropColumn(['dp_metode', 'dp_cash', 'dp_transfer']);
        });
    }
};
