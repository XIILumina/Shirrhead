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
        'cards',
        'invite_code',
    ];

    protected $casts = [
        'cards' => 'array', // Automatically decode/encode JSON
    ];

    public function players()
    {
        return $this->hasMany(Player::class);
    }
    public function lobby()
{
    return $this->belongsTo(Lobby::class, 'invite_code', 'invite_code');
}
}
