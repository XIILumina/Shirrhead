<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Cards extends Migration // Unique class name based on timestamp
{
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->string('suit');
            $table->string('value');
            $table->unsignedBigInteger('game_id');
            $table->unsignedBigInteger('player_id')->nullable();
            $table->string('location');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
            $table->foreign('player_id')->references('id')->on('players')->onDelete('cascade');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cards');
    }
}