<?php

namespace App\Http\Controllers;

use App\Models\Lobby;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class LobbyController extends Controller
{
    public function viewLobby($inviteCode)
    {
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
        $players = json_decode($lobby->players, true);

        return Inertia::render('Lobby', [
            'lobby' => $lobby,
            'players' => $players,
            'inviteCode' => $inviteCode,
        ]);
    }

    public function createLobby(Request $request)
    {
        try {
            if (!auth()->check()) {
                return redirect()->route('login'); // Redirect if not authenticated
            }
    
            $inviteCode = Str::random(5);
            $userId = auth()->id();
    
            $lobby = Lobby::create([
                'invite_code' => $inviteCode,
                'status' => 'waiting for players',
                'players' => json_encode([['id' => $userId, 'ready' => false]]),
            ]);
    
            Log::info("Lobby created: " . $inviteCode);
    
            // Return the redirect URL for the frontend to handle
            return response()->json([
                'message' => 'Lobby created',
                'lobby' => $lobby,
                'redirect_url' => route('lobby.view', ['inviteCode' => $lobby->invite_code]),
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating lobby: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create lobby'], 500);
        }
    }

    public function joinLobby(Request $request, $inviteCode)
    {
        try {
            $userId = auth()->id(); // Use auth()->id() consistently
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

            $players = json_decode($lobby->players, true);

            if (in_array($userId, array_column($players, 'id'))) {
                return response()->json(['message' => 'Already in lobby']);
            }

            $players[] = ['id' => $userId, 'ready' => false];
            $lobby->players = json_encode($players);
            $lobby->save();

            return response()->json(['message' => 'Joined lobby', 'lobby' => $lobby]);
        } catch (\Exception $e) {
            Log::error('Error joining lobby: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to join lobby'], 500);
        }
    }

    public function markReady(Request $request, $inviteCode)
    {
        try {
            $userId = auth()->id(); // Use auth()->id() consistently
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
            $players = json_decode($lobby->players, true);

            foreach ($players as &$player) {
                if ($player['id'] === $userId) {
                    $player['ready'] = true;
                    break;
                }
            }

            $lobby->players = json_encode($players);
            $allReady = collect($players)->every(fn($p) => $p['ready']);

            if ($allReady && count($players) >= 2) {
                $lobby->status = 'ready';
            }

            $lobby->save();

            return response()->json(['message' => 'Marked ready', 'lobby' => $lobby]);
        } catch (\Exception $e) {
            Log::error('Error marking ready: ' . $e->getMessage());
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