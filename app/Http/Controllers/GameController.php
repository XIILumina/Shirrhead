<?php

namespace App\Http\Controllers;


use App\Models\Lobby;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
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
                'invite_code' => null,
            ]);
    
            // Add player to the game
            Player::create([
                'game_id' => $game->id,
                'user_id' => $user->id,
                'visible_cards' => json_encode([]),
                'hidden_cards' => json_encode([]),
                'hand' => json_encode([]),
                'position' => 0,
            ]);
    
            // Add AI as an enemy
            Player::create([
                'game_id' => $game->id,
                'user_id' => null, // AI doesn't need a user ID
                'visible_cards' => json_encode([]),
                'hidden_cards' => json_encode([]),
                'hand' => json_encode([]),
                'position' => 1,
            ]);
    
            return response()->json([
                'message' => 'Solo game created',
                'game' => $game,
                'redirect_url' => route('game.view', ['game' => $game->id])
            ], 201);
        } catch (\Exception $e) {
            Log::error('Error creating solo game: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create solo game'], 500);
        }
    }
    

    public function startSoloGame(Game $game)
    {
        $deck = $this->generateDeck();
        shuffle($deck);

        $playerCards = array_splice($deck, 0, 3);
        $aiCards = array_splice($deck, 0, 3);

        $game->cards = json_encode(['deck' => $deck, 'pile' => []]);
        $game->status = 'ongoing';
        $game->save();

        // Assign cards to the player
        $player = Player::where('game_id', $game->id)->whereNotNull('user_id')->first();
        $player->hand = json_encode($playerCards);
        $player->save();

        // Assign cards to the AI
        $ai = Player::where('game_id', $game->id)->whereNull('user_id')->first();
        $ai->hand = json_encode($aiCards);
        $ai->save();
    }
    private function initializeDeck()
    {
        $suits = ['Hearts', 'Diamonds', 'Clubs', 'Spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $deck = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = ['value' => $value, 'suit' => $suit];
            }
        }

        // Shuffle the deck
        shuffle($deck);

        return $deck;
    }

    private function distributeCards(Game $game, $players)
    {
        $deck = $game->cards;
        $hands = [];

        // Give each player 3 visible and 3 hidden cards, then 3 cards in hand
        foreach ($players as $player) {
            $hands[$player['id']] = [
                'hand' => array_splice($deck, 0, 3),
                'visible_cards' => array_splice($deck, 0, 3),
                'hidden_cards' => array_splice($deck, 0, 3),
            ];
        }

        $game->cards = $deck;
        $game->save();

        // Add players to the game
        foreach ($players as $player) {
            Player::create([
                'user_id' => $player['id'],
                'game_id' => $game->id,
                'hand' => $hands[$player['id']]['hand'],
                'visible_cards' => $hands[$player['id']]['visible_cards'],
                'hidden_cards' => $hands[$player['id']]['hidden_cards'],
                'position' => array_search($player, $players), // Position in player array
            ]);
        }
    }
    public function createGameFromLobby(Lobby $lobby)
    {
        $players = $lobby->players;

        // Initialize a shuffled deck
        $deck = $this->initializeDeck();

        // Create game
        $game = Game::create([
            'name' => 'Game from Lobby ' . $lobby->invite_code,
            'status' => 'in-progress',
            'current_turn' => $players[0]['id'], // First player starts
            'cards' => $deck,
            'invite_code' => $lobby->invite_code,
        ]);

        // Distribute cards to players
        $this->distributeCards($game, $players);

        return $game;
    }

    public function playTurn(Game $game)
    {
        $currentTurn = $game->current_turn;
        $players = Player::where('game_id', $game->id)->get();
        $deck = json_decode($game->cards, true)['deck'];
        $pile = json_decode($game->cards, true)['pile'];

        $player = $players[$currentTurn];
        $hand = json_decode($player->hand, true);

        if ($currentTurn == 1) {
            // AI logic
            $aiCard = $this->aiPlay($hand, $pile);
            if ($aiCard) {
                $pile[] = $aiCard;
                $hand = array_filter($hand, fn($card) => $card !== $aiCard);
            } else {
                $hand[] = array_shift($deck); // Draw from deck if no playable card
            }
        }

        // Update game state
        $game->cards = json_encode(['deck' => $deck, 'pile' => $pile]);
        $game->current_turn = ($currentTurn + 1) % count($players);
        $game->save();

        $player->hand = json_encode($hand);
        $player->save();
    }

    private function aiPlay($hand, $pile)
    {
        $topCard = end($pile);
        foreach ($hand as $card) {
            if ($this->isCardPlayable($card, $topCard)) {
                return $card;
            }
        }
        return null; // No playable card
    }

    private function isCardPlayable($card, $topCard)
    {
        return $topCard === null || $card['value'] === $topCard['value'] || $card['suit'] === $topCard['suit'];
    }
}
