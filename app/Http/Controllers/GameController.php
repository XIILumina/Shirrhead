<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    // View game details
    public function viewgame(Request $request, $game_id)
    {
        $game = Game::findOrFail($game_id);
        $players = Player::where('game_id', $game_id)->get();
    
        return Inertia::render('Game', [
            'game' => $game,
            'players' => $players,
        ]);
    }

    // Create a new game and generate a unique invite code
    public function createGame()
    {
        $userId = auth()->id();
    
        $inviteCode = Str::random(6);
    
        $game = Game::create([
            'name' => '██▅▇██▇▆▅▄▄▇',
            'status' => 'pending',
            'cards' => json_encode([]),
            'invite_code' => $inviteCode,
        ]);
    
        Player::create([
            'user_id' => $userId,
            'game_id' => $game->id,
            'hand' => json_encode([]),
            'visible_cards' => json_encode([]),
            'hidden_cards' => json_encode([]),
            'position' => 0,
        ]);
    
        return response()->json(['game_id' => $game->id]);
    }

    // Join game by invite code
    public function joinGameByCode(Request $request)
    {
        $userId = auth()->id();
        $inviteCode = $request->input('invite_code');
    
        $game = Game::where('invite_code', $inviteCode)->first();
    
        if (!$game) {
            return response()->json(['message' => 'Invalid game invite code.']);
        }
    
        if ($game->status !== 'pending') {
            return response()->json(['message' => 'Cannot join this game, it has already started.']);
        }
    
        // Ensure the player isn't already in the game
        if (Player::where('game_id', $game->id)->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'You are already in this game.']);
        }
    
        $playerCount = Player::where('game_id', $game->id)->count();
    
        if ($playerCount >= 4) {
            return response()->json(['message' => 'Game is full.']);
        }
    
        Player::create([
            'user_id' => $userId,
            'game_id' => $game->id,
            'hand' => json_encode([]),
            'visible_cards' => json_encode([]),
            'hidden_cards' => json_encode([]),
            'position' => $playerCount,
        ]);
    
        if ($playerCount + 1 >= 2) {
            $game->update(['status' => 'ongoing']);
        }
    
        return response()->json(['message' => 'Joined game.', 'game_id' => $game->id]);
    }
}
