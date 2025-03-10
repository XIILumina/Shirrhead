<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::create('lobbies', function (Blueprint $table) {
            $table->id();
            $table->string('invite_code')->unique();
            $table->string('status', 255)->default('waiting'); // Default status
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lobbies');
    }
};