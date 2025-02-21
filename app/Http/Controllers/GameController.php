<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Events\CardPlayed;
use Inertia\Inertia;
use App\Models\LobbyPlayer;

class GameController extends Controller
{
    private function generateDeck($gameId)
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        $deck = [];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                $deck[] = Card::create([
                    'game_id' => $gameId,
                    'suit' => $suit,
                    'value' => $value,
                    'location' => 'deck',
                    'position' => count($deck),
                ]);
            }
        }

        // Shuffle deck
        $deck = Card::where('game_id', $gameId)->where('location', 'deck')->get()->shuffle();
        foreach ($deck as $index => $card) {
            $card->update(['position' => $index]);
        }

        return $deck;
    }
    public function createGameFromLobby($lobby)
    {
        try {
            $game = Game::create([
                'name' => 'Game from Lobby',
                'status' => 'ongoing',
                'invite_code' => $lobby->invite_code,
            ]);
            $game->cards = json_encode($this->generateDeck($game->id));
            $game->save();

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
    public function createSoloGame(Request $request)
    {
        try {
            $userId = Auth::id();
            $difficulty = $request->input('difficulty', 'easy');
            $numBots = $difficulty === 'hard' ? 3 : ($difficulty === 'medium' ? 2 : 1);
    
            $game = Game::create([
                'name' => 'Solo Game',
                'status' => 'ongoing',
                'invite_code' => Str::random(6),
                'start_time' => now(),
            ]);
    
            $this->generateDeck($game->id);
    
            // Human player
            $player = Player::create([
                'user_id' => $userId,
                'game_id' => $game->id,
                'position' => 0,
            ]);
    
            $game->current_turn = $player->id; // Set initial turn to player.id
            $game->save();
    
            $deck = Card::where('game_id', $game->id)->where('location', 'deck')->orderBy('position')->get();
            foreach (['hand', 'visible', 'hidden'] as $location) {
                for ($i = 0; $i < 3; $i++) {
                    $card = $deck->shift();
                    $card->update(['player_id' => $player->id, 'location' => $location, 'position' => $i]);
                }
            }
    
            // Bots
            for ($i = 0; $i < $numBots; $i++) {
                $bot = Player::create([
                    'user_id' => null,
                    'game_id' => $game->id,
                    'position' => $i + 1,
                    'is_bot' => true,
                ]);
                foreach (['hand', 'visible', 'hidden'] as $location) {
                    for ($j = 0; $j < 3; $j++) {
                        $card = $deck->shift();
                        $card->update(['player_id' => $bot->id, 'location' => $location, 'position' => $j]);
                    }
                }
            }
    
            return response()->json(['redirect_url' => '/game/' . $game->id]);
        } catch (\Exception $e) {
            Log::error('Error creating solo game: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to create solo game: ' . $e->getMessage()], 500);
        }
    }

public function getGameState($gameId)
{
    $user = Auth::user();
    $game = Game::findOrFail($gameId);
    $player = Player::where('game_id', $gameId)->where('user_id', $user->id)->first();

    if (!$player) {
        return response()->json(['message' => 'You are not part of this game'], 403);
    }

    $hand = Card::where('game_id', $gameId)->where('player_id', $player->id)->where('location', 'hand')->orderBy('position')->get()->toArray();
    $visible = Card::where('game_id', $gameId)->where('player_id', $player->id)->where('location', 'visible')->orderBy('position')->get()->toArray();
    $hidden = Card::where('game_id', $gameId)->where('player_id', $player->id)->where('location', 'hidden')->orderBy('position')->get()->toArray();
    $pile = Card::where('game_id', $gameId)->where('location', 'pile')->orderBy('position')->get()->toArray();
    $deck = Card::where('game_id', $gameId)->where('location', 'deck')->orderBy('position')->get()->toArray();

    $enemies = Player::where('game_id', $gameId)->where('id', '!=', $player->id)->with(['cards' => function ($query) {
        $query->whereIn('location', ['visible', 'hidden']);
    }])->get()->map(function ($enemy) {
        return [
            'name' => $enemy->is_bot ? 'Bot' : ($enemy->user->name ?? 'Unknown'),
            'visible_cards' => $enemy->cards->where('location', 'visible')->values()->toArray(),
            'hidden_cards' => $enemy->cards->where('location', 'hidden')->values()->toArray(),
        ];
    })->toArray();

    return response()->json([
        'hand' => $hand,
        'visible_cards' => $visible,
        'hidden_cards' => $hidden,
        'pile' => $pile,
        'deck' => $deck,
        'turn' => $game->current_turn == $player->id, // Compare with player.id
        'enemies' => $enemies,
    ]);
}
    // Added viewgame method
    public function viewgame(Request $request, $game_id)
    {
        $game = Game::findOrFail($game_id);
        $players = Player::where('game_id', $game_id)->get();

        return Inertia::render('Game', [
            'game' => $game,
            'players' => $players,
        ]);
    }
}