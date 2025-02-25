<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('whatsapp_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('session_id')->unique();
            $table->string('api_key')->unique();
            $table->string('status')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('qr_code_endpoint')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('whatsapp_devices');
    }
};
