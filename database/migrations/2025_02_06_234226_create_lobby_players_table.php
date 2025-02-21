<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLobbyPlayersTable extends Migration
{
    public function up()
    {
        Schema::create('lobby_players', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lobby_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->boolean('ready')->default(false);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('lobby_players');
    }
}