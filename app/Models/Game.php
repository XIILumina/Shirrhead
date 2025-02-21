<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Game extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'status',
        'current_turn',
        'invite_code',
        'start_time', // Added since it's used in createSoloGame
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }

    public function cards()
    {
        return $this->hasMany(Card::class);
    }
}