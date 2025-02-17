<?php
namespace App\Http\Controllers;

use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use App\Events\CardPlayed;
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

    public function playCard(Request $request, $gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
    
        if (!$player) {
            return response()->json(['message' => 'You are not part of this game'], 403);
        }
    
        $card = $request->input('card');
        $game = Game::findOrFail($gameId);
    
        // Update game state (e.g., move card from hand to pile)
        // ...
    
        // Broadcast the CardPlayed event
        Broadcast::channel('game.' . $gameId, function ($user, $gameId) {
            return $user->can('view-game', Game::find($gameId));
        });
    
        broadcast(new CardPlayed($gameId, $card))->toOthers();
    
        return response()->json(['message' => 'Card played successfully']);
    }

    
    public function getGameState($gameId)
    {
        $user = Auth::user();
        $game = Game::findOrFail($gameId);
    
        // Get the current player's state
        $player = Player::where('game_id', $gameId)->where('user_id', $user->id)->first();
    
        if (!$player) {
            return response()->json(['message' => 'You are not part of this game'], 403);
        }
    
        // Get the opponent's visible cards
        $opponents = Player::where('game_id', $gameId)
            ->where('user_id', '!=', $user->id)
            ->get();
    
        $enemyVisibleCards = [];
        foreach ($opponents as $opponent) {
            $enemyVisibleCards = array_merge($enemyVisibleCards, json_decode($opponent->visible_cards, true));
        }
    
        // Decode game cards
        $gameCards = json_decode($game->cards, true);
    
        return response()->json([
            'hand' => json_decode($player->hand, true),
            'visible_cards' => json_decode($player->visible_cards, true),
            'hidden_cards' => json_decode($player->hidden_cards, true),
            'pile' => $gameCards['pile'] ?? [],
            'deck' => $gameCards['deck'] ?? [],
            'turn' => $game->current_turn == $user->id,
            'enemy_visible_cards' => $enemyVisibleCards, // Add enemy's visible cards
        ]);
    }




    public function createSoloGame(Request $request)
    {
        try {
            $userId = Auth::id();
            $difficulty = $request->input('difficulty', 'easy'); // Default to 'easy'
    
            // Generate a unique invite code
            $inviteCode = Str::random(6);
    
            // Create the game
            $game = Game::create([
                'name' => 'Solo Game',
                'status' => 'ongoing',
                'cards' => json_encode([]),
                'invite_code' => $inviteCode,
                'start_time' => now(),
            ]);
    
            // Generate deck and shuffle it
            $deck = $this->generateDeck();
            shuffle($deck);
    
            // Deal cards to the player
            $hand = array_splice($deck, 0, 3);
            $visible = array_splice($deck, 0, 3);
            $hidden = array_splice($deck, 0, 3);
    
            // Create the player
            Player::create([
                'user_id' => $userId,
                'game_id' => $game->id,
                'hand' => json_encode($hand),
                'visible_cards' => json_encode($visible),
                'hidden_cards' => json_encode($hidden),
                'position' => 0,
            ]);
    
            // Add bots based on difficulty
            $numBots = 1; // Default to easy (1 bot)
            if ($difficulty === 'medium') {
                $numBots = 2;
            } elseif ($difficulty === 'hard') {
                $numBots = 3;
            }
    
            for ($i = 1; $i <= $numBots; $i++) {
                // Deal cards to the bot
                $botHand = array_splice($deck, 0, 3);
                $botVisible = array_splice($deck, 0, 3);
                $botHidden = array_splice($deck, 0, 3);
    
                Player::create([
                    'user_id' => null, // Set user_id to null for bots
                    'game_id' => $game->id,
                    'hand' => json_encode($botHand),
                    'visible_cards' => json_encode($botVisible),
                    'hidden_cards' => json_encode($botHidden),
                    'position' => $i,
                    'is_bot' => true, // Add a flag to identify bots
                ]);
            }
    
            // Set game state
            $game->cards = json_encode([
                'deck' => $deck, 
                'pile' => [],
            ]);
            $game->save();
    
            return response()->json([
                'redirect_url' => '/game/' . $game->id,
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating solo game: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create solo game'], 500);
        }
    }





    // Create a new game and generate a unique invite code
    public function createGame()
    {
        $userId = Auth::id();
    
        $inviteCode = Str::random(6);
    
        $game = Game::create([
            'name' => '██▅▇██▇▆▅▄▄▇',
            'status' => 'pending',
            'cards' => json_encode([]),
            'invite_code' => $inviteCode,
            'start_time' => now(),
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
            $lobbyPlayers = LobbyPlayer::where('lobby_id', $lobby->id)->get();
            foreach ($lobbyPlayers as $index => $lobbyPlayer) {
                Player::create([
                    'user_id' => $lobbyPlayer->user_id,
                    'game_id' => $game->id,
                    'hand' => json_encode([]),
                    'position' => $index,
                ]);
            }

            return $game;
        } catch (\Exception $e) {
            throw $e;
        }
    }




    // Join game by invite code
    public function joinGameByCode(Request $request)
    {
        $userId = Auth::id();
        $inviteCode = $request->input('invite_code');

        // Find the lobby by invite code
        $lobby = Lobby::where('invite_code', $inviteCode)->first();

        if (!$lobby) {
            return response()->json(['message' => 'Invalid lobby invite code.'], 404);
        }

        if ($lobby->status !== 'waiting') {
            return response()->json(['message' => 'Cannot join this lobby, it has already started.'], 400);
        }

        // Ensure the player isn't already in the lobby
        if (LobbyPlayer::where('lobby_id', $lobby->id)->where('user_id', $userId)->exists()) {
            return response()->json(['message' => 'You are already in this lobby.'], 400);
        }

        $playerCount = LobbyPlayer::where('lobby_id', $lobby->id)->count();

        if ($playerCount >= 4) {
            return response()->json(['message' => 'Lobby is full.'], 400);
        }

        LobbyPlayer::create([
            'lobby_id' => $lobby->id,
            'user_id' => $userId,
            'ready' => false,
        ]);

        return response()->json(['message' => 'Joined the lobby successfully']);
    }

    
}
