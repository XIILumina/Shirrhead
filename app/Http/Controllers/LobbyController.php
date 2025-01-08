<?php
namespace App\Http\Controllers;

use App\Models\Lobby;
use App\Models\Player;
use Inertia\Inertia;
use App\Models\Game;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class LobbyController extends Controller
{



    public function viewLobby($inviteCode)
    {
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
        $players = $lobby->players;

        // Return the view with the lobby data
        return Inertia::render('Lobby', [
            'lobby' => $lobby,
            'players' => $players,
            'inviteCode' => $inviteCode,
        ]);

    }






    public function createLobby()
    {
        try {
            $inviteCode = Str::random(5);
            $userId = auth()->id(); // Get the host's user ID

            // Create the lobby
            $lobby = Lobby::create([
                'invite_code' => $inviteCode,
                'status' => 'waiting for players', // Initialize status to 'waiting for players'
                'players' => json_encode([['id' => $userId, 'ready' => false]]), // Add the host as the first player
            ]);

            // Redirect to the lobby view (assuming a route 'lobby.view' exists)
            return redirect()->route('lobby.view', ['inviteCode' => $inviteCode]);

        } catch (\Exception $e) {
            \Log::error('Error creating lobby: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create lobby'], 500);
        }
    }




    // Join a lobby
    public function joinLobby(Request $request, $inviteCode)
    {
        $userId = auth()->id();
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

        // Check if user is already in the lobby
        $players = $lobby->players;
        if (in_array($userId, array_column($players, 'id'))) {
            return response()->json(['message' => 'Already in lobby']);
        }

        // Add the user to the lobby's player list
        $players[] = ['id' => $userId, 'ready' => false]; // Add new player
        $lobby->players = $players;
        $lobby->save();

        // Redirect the user to the lobby page (assuming a route 'lobby.view' exists)
        return redirect()->route('lobby.view', ['inviteCode' => $inviteCode])
            ->with('lobby', $lobby); // Optionally pass the lobby data
    }









    // Mark a player as ready
    public function markReady(Request $request, $inviteCode)
    {
        $userId = auth()->id();
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

        $players = $lobby->players;
        foreach ($players as &$player) {
            if ($player['id'] === $userId) {
                $player['ready'] = true;
                break;
            }
        }

        // Update the players and lobby status
        $lobby->players = $players;
        $allReady = collect($players)->every(fn($p) => $p['ready']);

        if ($allReady && count($players) >= 2) {
            $lobby->status = 'ready';
        }

        $lobby->save();

        return response()->json(['message' => 'Marked ready', 'lobby' => $lobby]);
    }








    // Start game from lobby
    public function startGame($inviteCode)
    {
        $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();
        if ($lobby->status !== 'ready') {
            return response()->json(['message' => 'Not all players are ready']);
        }

        // Create game
        $gameController = app(GameController::class);
        $game = $gameController->createGameFromLobby($lobby);

        // Update lobby status
        $lobby->status = 'started';
        $lobby->save();

        return response()->json(['message' => 'Game started', 'game_id' => $game->id]);
    }
}
