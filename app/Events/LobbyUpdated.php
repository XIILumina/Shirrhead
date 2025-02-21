<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class LobbyUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public $lobby;
    public $players;

    public function __construct($lobby, $players)
    {
        $this->lobby = $lobby;
        $this->players = $players;
    }

    public function broadcastOn()
    {
        return new Channel('lobby.' . $this->lobby->invite_code);
    }

    public function broadcastWith()
    {
        return [
            'lobby' => $this->lobby,
            'players' => $this->players,
        ];
    }
}