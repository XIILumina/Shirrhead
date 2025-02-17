<?php

namespace App\Http\Controllers;

use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LobbyPlayerController extends Controller
{
    /**
     * Mark a player as ready in the lobby.
     *
     * @param Request $request
     * @param int $lobbyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function markAsReady(Request $request, $lobbyId)
    {
        $user = Auth::user();

        // Find the lobby player entry
        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobbyId)
            ->where('user_id', $user->id)
            ->first();

        if (!$lobbyPlayer) {
            return response()->json(['message' => 'You are not part of this lobby.'], 403);
        }

        // Mark the player as ready
        $lobbyPlayer->update(['ready' => true]);

        return response()->json(['message' => 'You are now ready!']);
    }

    /**
     * Get all players in the lobby.
     *
     * @param int $lobbyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getPlayers($lobbyId)
    {
        $lobby = Lobby::find($lobbyId);

        if (!$lobby) {
            return response()->json(['message' => 'Lobby not found.'], 404);
        }

        // Fetch all players in the lobby with their user details
        $players = LobbyPlayer::with('user')
            ->where('lobby_id', $lobbyId)
            ->get()
            ->map(function ($lobbyPlayer) {
                return [
                    'id' => $lobbyPlayer->user->id,
                    'name' => $lobbyPlayer->user->name,
                    'ready' => $lobbyPlayer->ready,
                ];
            });

        return response()->json(['players' => $players]);
    }

    /**
     * Remove a player from the lobby.
     *
     * @param Request $request
     * @param int $lobbyId
     * @return \Illuminate\Http\JsonResponse
     */
    public function leaveLobby(Request $request, $lobbyId)
    {
        $user = Auth::user();

        // Find the lobby player entry
        $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobbyId)
            ->where('user_id', $user->id)
            ->first();

        if (!$lobbyPlayer) {
            return response()->json(['message' => 'You are not part of this lobby.'], 403);
        }

        // Remove the player from the lobby
        $lobbyPlayer->delete();

        // Check if the lobby is now empty and delete it if necessary
        $lobby = Lobby::find($lobbyId);
        if ($lobby && $lobby->players()->count() === 0) {
            $lobby->delete();
        }

        return response()->json(['message' => 'You have left the lobby.']);
    }
}