<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('status', ['pending', 'ongoing', 'completed']);
            $table->unsignedBigInteger('current_turn')->nullable();
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->json('cards'); // Stores game deck and pile
            $table->timestamps();
            $table->string('invite_code', 6)->unique()->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};