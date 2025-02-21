<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\LobbyPlayer;
use App\Models\Lobby;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('lobby.{inviteCode}', function ($user, $inviteCode) {
    return LobbyPlayer::where('lobby_id', Lobby::where('invite_code', $inviteCode)->first()->id)
        ->where('user_id', $user->id)
        ->exists();
});