<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LobbyPlayer extends Model
{
    use HasFactory;

    protected $fillable = ['lobby_id', 'user_id', 'ready'];

    /**
     * Get the lobby associated with the player.
     */
    public function lobby()
    {
        return $this->belongsTo(Lobby::class);
    }

    /**
     * Get the user associated with the player.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}