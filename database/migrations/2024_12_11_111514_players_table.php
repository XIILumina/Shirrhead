<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('players', function (Blueprint $table) {
            $table->id();
        $table->unsignedBigInteger('user_id')->nullable(); // Allow null for AI players
        $table->unsignedBigInteger('game_id');
        $table->json('hand')->nullable();
        $table->json('visible_cards')->nullable();
        $table->json('hidden_cards')->nullable();
        $table->integer('position');
        $table->timestamps();

        // Foreign key constraints
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
