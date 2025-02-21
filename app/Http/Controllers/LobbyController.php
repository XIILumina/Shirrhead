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
use App\Models\Card;
use App\Models\Game;
use App\Models\Player;

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
            })->toArray();

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
            })->toArray();

        return response()->json([
            'lobby' => $lobby,
            'players' => $players,
        ]);
    }

    public function createLobby(Request $request)
    {
        $userId = Auth::id();
        $inviteCode = Str::random(6);

        try {
            $lobby = Lobby::create([
                'invite_code' => $inviteCode,
                'status' => 'waiting',
            ]);

            LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $userId,
                'ready' => false,
            ]);

            $players = LobbyPlayer::with('user')
                ->where('lobby_id', $lobby->id)
                ->get()
                ->map(function ($lobbyPlayer) {
                    return [
                        'id' => $lobbyPlayer->user->id,
                        'name' => $lobbyPlayer->user->name,
                        'ready' => $lobbyPlayer->ready,
                    ];
                })->toArray();

            broadcast(new LobbyUpdated($lobby, $players))->toOthers();

            return response()->json([
                'message' => 'Lobby created successfully',
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
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

            if ($lobby->status !== 'waiting') {
                return response()->json(['message' => 'Lobby is not open for joining'], 400);
            }

            if (LobbyPlayer::where('lobby_id', $lobby->id)->where('user_id', $userId)->exists()) {
                return response()->json(['message' => 'You are already in this lobby'], 400);
            }

            LobbyPlayer::create([
                'lobby_id' => $lobby->id,
                'user_id' => $userId,
                'ready' => false,
            ]);

            $players = LobbyPlayer::with('user')
                ->where('lobby_id', $lobby->id)
                ->get()
                ->map(function ($lobbyPlayer) {
                    return [
                        'id' => $lobbyPlayer->user->id,
                        'name' => $lobbyPlayer->user->name,
                        'ready' => $lobbyPlayer->ready,
                    ];
                })->toArray();

            broadcast(new LobbyUpdated($lobby, $players))->toOthers();

            return response()->json([
                'message' => 'Joined lobby successfully',
                'redirect_url' => '/lobby/' . $lobby->invite_code,
            ]);
        } catch (\Exception $e) {
            Log::error('Error joining lobby: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to join lobby'], 500);
        }
    }

    public function markReady(Request $request, $inviteCode)
    {
        try {
            $userId = Auth::id();
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

            $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $lobbyPlayer->update(['ready' => true]);

            $allReady = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('ready', false)
                ->doesntExist();

            if ($allReady && LobbyPlayer::where('lobby_id', $lobby->id)->count() >= 2) {
                $lobby->update(['status' => 'ready']);
            }

            $players = LobbyPlayer::with('user')
                ->where('lobby_id', $lobby->id)
                ->get()
                ->map(function ($lobbyPlayer) {
                    return [
                        'id' => $lobbyPlayer->user->id,
                        'name' => $lobbyPlayer->user->name,
                        'ready' => $lobbyPlayer->ready,
                    ];
                })->toArray();

            broadcast(new LobbyUpdated($lobby, $players))->toOthers();

            return response()->json(['message' => 'Marked as ready']);
        } catch (\Exception $e) {
            Log::error('Error marking ready: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to mark ready'], 500);
        }
    }    public function unReady(Request $request, $inviteCode)
    {
        try {
            $userId = Auth::id();
            $lobby = Lobby::where('invite_code', $inviteCode)->firstOrFail();

            $lobbyPlayer = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('user_id', $userId)
                ->firstOrFail();

            $lobbyPlayer->update(['ready' => false]);

            $allReady = LobbyPlayer::where('lobby_id', $lobby->id)
                ->where('ready', false)
                ->doesntExist();

            if ($allReady && LobbyPlayer::where('lobby_id', $lobby->id)->count() >= 2) {
                $lobby->update(['status' => 'ready']);
            }

            $players = LobbyPlayer::with('user')
                ->where('lobby_id', $lobby->id)
                ->get()
                ->map(function ($lobbyPlayer) {
                    return [
                        'id' => $lobbyPlayer->user->id,
                        'name' => $lobbyPlayer->user->name,
                        'ready' => $lobbyPlayer->ready,
                    ];
                })->toArray();

            broadcast(new LobbyUpdated($lobby, $players))->toOthers();

            return response()->json(['message' => 'Marked as unready']);
        } catch (\Exception $e) {
            Log::error('Error marking unready: ' . $e->getMessage());
            return response()->json(['message' => 'Failed to mark unready'], 500);
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

    public function createGameFromLobby($lobby)
{
    try {
        $game = Game::create([
            'name' => 'Game from Lobby',
            'status' => 'ongoing',
            'invite_code' => $lobby->invite_code,
            'start_time' => now(),
        ]);

        $this->generateDeck($game->id);

        $lobbyPlayers = LobbyPlayer::where('lobby_id', $lobby->id)->get();
        $playerCount = $lobbyPlayers->count();
        $numBots = max(0, 4 - $playerCount); // Fill up to 4 players with bots

        foreach ($lobbyPlayers as $index => $lobbyPlayer) {
            $player = Player::create([
                'user_id' => $lobbyPlayer->user_id,
                'game_id' => $game->id,
                'position' => $index,
                'is_bot' => false,
            ]);

            $deck = Card::where('game_id', $game->id)->where('location', 'deck')->orderBy('position')->get();
            foreach (['hand', 'visible', 'hidden'] as $location) {
                for ($i = 0; $i < 3; $i++) {
                    $card = $deck->shift();
                    $card->update(['player_id' => $player->id, 'location' => $location, 'position' => $i]);
                }
            }

            if ($index === 0) {
                $game->current_turn = $player->id;
                $game->save();
            }
        }

        // Add bots if needed
        for ($i = $playerCount; $i < $playerCount + $numBots; $i++) {
            $bot = Player::create([
                'user_id' => null,
                'game_id' => $game->id,
                'position' => $i,
                'is_bot' => true,
            ]);

            $deck = Card::where('game_id', $game->id)->where('location', 'deck')->orderBy('position')->get();
            foreach (['hand', 'visible', 'hidden'] as $location) {
                for ($j = 0; $j < 3; $j++) {
                    $card = $deck->shift();
                    $card->update(['player_id' => $bot->id, 'location' => $location, 'position' => $j]);
                }
            }
        }

        return $game;
    } catch (\Exception $e) {
        Log::error('Error creating game from lobby: ' . $e->getMessage());
        throw $e;
    }
}

    private function generateDeck($gameId)
    {
        $suits = ['hearts', 'diamonds', 'clubs', 'spades'];
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];

        foreach ($suits as $suit) {
            foreach ($values as $value) {
                Card::create([
                    'game_id' => $gameId,
                    'suit' => $suit,
                    'value' => $value,
                    'location' => 'deck',
                    'position' => Card::where('game_id', $gameId)->where('location', 'deck')->count(),
                ]);
            }
        }

        $deck = Card::where('game_id', $gameId)->where('location', 'deck')->get()->shuffle();
        foreach ($deck as $index => $card) {
            $card->update(['position' => $index]);
        }
    }
}