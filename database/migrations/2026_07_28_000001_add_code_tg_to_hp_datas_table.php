<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('hp_datas', 'code_tg')) {
            Schema::table('hp_datas', function (Blueprint $table) {
                $table->string('code_tg')->nullable()->after('type')->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hp_datas', 'code_tg')) {
            Schema::table('hp_datas', function (Blueprint $table) {
                $table->dropColumn('code_tg');
            });
        }
    }
};
