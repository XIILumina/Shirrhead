<?php
namespace App\Http\Controllers;

use Inertia\Inertia;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GameController extends Controller
{
    // View game details (Game 1 page)
    public function viewgame(Request $request, $game_id)
    {
        // Fetch the game data using the $game_id parameter
        $game = Game::findOrFail($game_id); // Make sure to fetch the game based on the ID
        $players = Player::where('game_id', $game_id)->get(); // Fetch players in that game
    
        // Return the game and players data to Inertia
        return Inertia::render('Game', [
            'game' => $game, // Send the game data
            'players' => $players, // Send the player data
        ]);
    }

    // Create a new game and generate a unique invite code
    public function createGame()
    {
        $userId = auth()->id();
    
        // Generate a unique invite code (5-6 digit random number)
        $inviteCode = Str::random(6); // Using Laravel's Str helper to generate a random string
    
        // Create the game
        $game = Game::create([
            'name' => 'Game ' . $inviteCode,
            'status' => 'pending',
            'cards' => json_encode([]),
            'invite_code' => $inviteCode, // Store the invite code
        ]);
    
        // Add the host as the first player
        Player::create([
            'user_id' => $userId,
            'game_id' => $game->id,
            'hand' => json_encode([]),
            'visible_cards' => json_encode([]),
            'hidden_cards' => json_encode([]),
            'position' => 0, // Host is always position 0
        ]);
    
        // Return the game ID to the frontend
        return response()->json(['game_id' => $game->id]); // Send game_id to frontend
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

        // Check if the user is already in the game
        if (Player::where('game_id', $game->id)->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'You are already in this game.']);
        }

        // Add player to the game
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

        // Start the game if there are at least 2 players
        if ($playerCount + 1 >= 2) {
            $game->update(['status' => 'ongoing']);
        }

        return response()->json(['message' => 'Joined game.', 'game_id' => $game->id]);
    }

    // Handle card placement (the logic for playing the card)
    public function startGame(Request $request, $game_id)
{
    $game = Game::findOrFail($game_id);
    $players = Player::where('game_id', $game_id)->get();

    // Ensure at least 2 players are in the game
    if ($players->count() <= 1) {
        return response()->json(['message' => 'Not enough players to start the game.']);
    }

    // Randomize the deck (using a simplified deck for this example)
    $deck = $this->generateDeck();
    shuffle($deck);

    // Deal cards to players (3 cards for each player)
    $hands = [];
    foreach ($players as $player) {
        $playerHand = array_splice($deck, 0, 3); // Deal 3 cards to each player
        $hands[$player->id] = $playerHand;
    }
    $Visible_cards = [];
    foreach ($players as $player) {
        $playerHand = array_splice($deck, 0, 3); // Deal 3 cards to each player
        $hands[$player->id] = $playerHand;
    }
    $hidden_cards = [];
    foreach ($players as $player) {
        $playerHand = array_splice($deck, 0, 3); // Deal 3 cards to each player
        $hands[$player->id] = $playerHand;
    }


    // Update each player's hand and game state
    foreach ($players as $player) {
        $player->hand = json_encode($hands[$player->id]);
        $player->save();
    }

    // Set the game status to 'ongoing'
    $game->status = 'ongoing';
    $game->cards = json_encode($deck); // Store the remaining deck
    $game->save();

    return response()->json(['message' => 'Game started and cards dealt']);
}

// Helper function to generate a deck of cards (simplified for this example)
private function generateDeck()
{
    $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
    $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    
    $deck = [];
    foreach ($suits as $suit) {
        foreach ($values as $value) {
            $deck[] = ['value' => $value, 'suit' => $suit];
        }
    }

    return $deck;
}
}
