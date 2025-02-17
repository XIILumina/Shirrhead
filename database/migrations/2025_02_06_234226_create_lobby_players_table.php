<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLobbyPlayersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lobby_players', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lobby_id'); // Foreign key to the lobby
            $table->unsignedBigInteger('user_id');  // Foreign key to the user
            $table->boolean('ready')->default(false); // Ready state of the player
            $table->timestamps();

            // Foreign key constraints
            $table->foreign('lobby_id')->references('id')->on('lobbies')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lobby_players');
    }
}