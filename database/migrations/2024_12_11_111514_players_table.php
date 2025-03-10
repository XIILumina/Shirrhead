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
            $table->unsignedBigInteger('user_id')->nullable(); // Allow null for bots
            $table->unsignedBigInteger('game_id');
            $table->json('hand')->nullable();
            $table->json('visible_cards')->nullable();
            $table->json('hidden_cards')->nullable();
            $table->integer('position')->default(0);
            $table->boolean('is_bot')->default(false); // Add a flag to identify bots
            $table->timestamps();
        
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('game_id')->references('id')->on('games')->onDelete('cascade');
        });
 }

    public function down(): void
    {
        Schema::dropIfExists('players');
    }
};
