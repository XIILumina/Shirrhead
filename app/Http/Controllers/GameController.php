<?php
namespace App\Http\Controllers;

use Auth;
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




    private function generateDeck()
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    
        $deck = [];
        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = ['suit' => $suit, 'value' => $value];
            }
        }
    
        shuffle($deck);
        return $deck;
    }





public function createSoloGame(Request $request)
{
    $user = Auth::user();

    try {
        // Create a new game
        $game = Game::create([
            "name" => "Solo Game",
            "current_turn" => rand(0, 1), // Random starting turn
            'status' => 'ongoing',
            'cards' => json_encode(['deck' => $this->generateDeck(), 'pile' => []]), // Initialize deck and pile
        ]);

        // Add player to the game
        Player::create([
            'game_id' => $game->id,
            'user_id' => $user->id,
            'visible_cards' => json_encode([]), // Empty visible cards
            'hidden_cards' => json_encode([]), // Empty hidden cards
            'hand' => json_encode([]), // Empty hand at the start
            'position' => $game->current_turn, // Set the player's position based on current turn
        ]);

        // Return the redirect URL after creating the solo game
        return response()->json([
            'message' => 'Solo game created',
            'game' => $game,
            'redirect_url' => route('game.view', ['game' => $game->id]) // Redirect to the game view
        ], 201);
    } catch (\Exception $e) {
        \Log::error('Error creating solo game: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to create solo game'], 500);
    }
}



public function getGameState($gameId)
{
    $user = auth()->user();
    $player = Player::where('game_id', $gameId)->where('user_id', $user->id)->first();

    if (!$player) {
        return response()->json(['message' => 'You are not part of this game'], 403);
    }

    $game = Game::find($gameId);
    $gameCards = json_decode($game->cards, true);

    return response()->json([
        'hand' => json_decode($player->hand, true),
        'pile' => $gameCards['pile'] ?? [],
        'deck' => $gameCards['deck'] ?? [],
        'turn' => $game->current_turn == $user->id,
    ]);
}





public function startSoloGame(Game $game)
{
    // Generate deck and shuffle it
    $deck = $this->generateDeck();
    shuffle($deck);

    // Deal 3 cards to the player
    $hands = [];
    $hands[0] = array_splice($deck, 0, 3); // Dealing 3 cards to the single player

    // Set the game state and save
    $game->cards = json_encode($deck); // Remaining deck
    $game->status = 'ongoing';
    $game->save();

    // Update player hand
    $player = Player::where('game_id', $game->id)->first();
    $player->hand = json_encode($hands[0]);
    $player->save();
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






    public function createGameFromLobby($lobby)
{
    try {
        $game = Game::create([
            'name' => 'Game from Lobby',
            'status' => 'ongoing',
            'cards' => json_encode($this->generateDeck()),
            'invite_code' => $lobby->invite_code,
        ]);

        // Add all lobby players to the game
        foreach ($lobby->players as $index => $player) {
            Player::create([
                'user_id' => $player['id'],
                'game_id' => $game->id,
                'hand' => json_encode([]),
                'position' => $index,
            ]);
        }

        return $game;
    } catch (\Exception $e) {
        \Log::error('Error creating game from lobby: ' . $e->getMessage());
        return response()->json(['message' => 'Failed to create game from lobby'], 500);
    } 
}





    // Join game by invite code
    public function joinGameByCode(Request $request)
{
    $userId = auth()->id();
    $inviteCode = $request->input('invite_code');

    // Find the game by invite code
    $game = Game::where('invite_code', $inviteCode)->first();

    if (!$game) {
        return response()->json(['message' => 'Invalid game invite code.'], 404);
    }

    if ($game->status !== 'pending') {
        return response()->json(['message' => 'Cannot join this game, it has already started.'], 400);
    }

    // Ensure the player isn't already in the game
    if (Player::where('game_id', $game->id)->where('user_id', $userId)->exists()) {
        return response()->json(['message' => 'You are already in this game.'], 400);
    }

    $playerCount = Player::where('game_id', $game->id)->count();

    if ($playerCount >= 4) {
        return response()->json(['message' => 'Game is full.'], 400);
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

    return Inertia::render('Game', [
        'game' => $game,
        'players' => Player::where('game_id', $game->id)->get(),
    ]);
}






    
}
