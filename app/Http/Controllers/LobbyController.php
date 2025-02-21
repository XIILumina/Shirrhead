<?php

namespace App\Http\Controllers;

use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Inertia\Inertia;
use App\Events\LobbyUpdated;
use App\Http\Controllers\GameController;


class LobbyController extends Controller
{
    public function viewLobby($inviteCode)
    {
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
        $players = LobbyPlayer::with('user')
            ->where('lobby_id', $lobby->id)
            ->get()
            ->map(function ($lobbyPlayer) {
                return [
                    'id' => $lobbyPlayer->user->id,
                    'name' => $lobbyPlayer->user->name,
                    'ready' => $lobbyPlayer->ready,
                ];
            });

        return Inertia::render('Lobby', [
            'lobby' => $lobby,
            'players' => $players,
            'inviteCode' => $inviteCode,
        ]);
    }

    public function fetchLobby($inviteCode)
{
    $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
    $players = LobbyPlayer::with('user')
        ->where('lobby_id', $lobby->id)
        ->get()
        ->map(function ($lobbyPlayer) {
            return [
                'id' => $lobbyPlayer->user->id,
                'name' => $lobbyPlayer->user->name,
                'ready' => $lobbyPlayer->ready,
            ];
        });

    return response()->json([
        'lobby' => $lobby,
        'players' => $players->toArray(), // Ensure it’s an array
    ]);
}

    public function createLobby(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
        ]);

        try {
            $userId = Auth::id();
            $inviteCode = Str::random(6);

            // Create the lobby
            $lobby = Lobby::create([
                'invite_code' => $inviteCode,
                'status' => 'waiting',
            ]);

            // Add the host to the lobby_players table
            LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $userId,
                'ready' => false,
            ]);

            return response()->json([
                'message' => 'Lobby created successfully',
                'lobby' => $lobby,
                'redirect_url' => '/lobby/' . $lobby->invite_code,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating lobby: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create lobby'], 500);
        }
    }

    public function joinLobby(Request $request, $inviteCode)
    {
        try {
            $userId = Auth::id();
            $lobby = Lobby::where('invite_code', $inviteCode)->first();

            if (!$lobby) {
                return response()->json(['message' => 'Invalid invite code'], 404);
            }

            if ($lobby->status !== 'waiting') {
                return response()->json(['message' => 'Lobby is not open for joining'], 400);
            }

            // Check if the user is already in the lobby
            if (LobbyPlayer::where('lobby_id', $lobby->id)->where('user_id', $userId)->exists()) {
                return response()->json(['message' => 'You are already in this lobby'], 400);
            }

            // Add the user to the lobby_players table
            LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $userId,
                'ready' => false,
            ]);

            return response()->json(['message' => 'Joined lobby successfully']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to join lobby'], 500);
        }
    }

    public function markReady(Request $request, $inviteCode)
    {
        try {
            $userId = Auth::id();
            $lobby = Lobby::where('invite_code', $inviteCode)->first();

            if (!$lobby) {
                return response()->json(['message' => 'Lobby not found'], 404);
            }

            $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('user_id', $userId)
                ->first();

            if (!$lobbyPlayer) {
                return response()->json(['message' => 'You are not part of this lobby'], 403);
            }

            $lobbyPlayer->update(['ready' => true]);

            $allReady = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('ready', false)
                ->doesntExist();

            if ($allReady && LobbyPlayer::where('lobby_id', $lobby->id)->count() >= 2) {
                $lobby->update(['status' => 'ready']);
            }

            // Broadcast the update
            $players = LobbyPlayer::with('user')
                ->where('lobby_id', $lobby->id)
                ->get()
                ->map(function ($lobbyPlayer) {
                    return [
                        'id' => $lobbyPlayer->user->id,
                        'name' => $lobbyPlayer->user->name,
                        'ready' => $lobbyPlayer->ready,
                    ];
                });

            broadcast(new LobbyUpdated($lobby, $players))->toOthers();

            return response()->json(['message' => 'Marked as ready']);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to mark ready'], 500);
        }
    }


    public function startGame($inviteCode)
    {
        try {
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

            if ($lobby->status !== 'ready') {
                return response()->json(['message' => 'Not all players are ready'], 400);
            }

            $gameController = app(GameController::class);
            $game = $gameController->createGameFromLobby($lobby);

            $lobby->status = 'started';
            $lobby->save();

            return response()->json(['message' => 'Game started', 'game_id' => $game->id]);
        } catch (\Exception $e) {
            Log::error('Error starting game: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to start game'], 500);
        }
    }
}