<?php

namespace App\Http\Controllers;


use App\Events\GameUpdated;
use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;

class PlayerController extends Controller
{
    public function playCard(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        $player = $game->players()->where('user_id', auth()->id())->first();
        $card = $request->input('card');

        if (!$player || !$game->isPlayerTurn($player)) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        // Validate and play the card
        if (!$player->playCard($card)) {
            return response()->json(['message' => 'Invalid card.'], 400);
        }

        // Check for game win condition
        if ($player->hand->isEmpty()) {
            $game->status = 'completed';
            $game->winner_id = $player->id;
            $game->save();
        } else {
            $game->nextTurn();
        }

        // Broadcast updated game state
        broadcast(new GameUpdated($game))->toOthers();

        return response()->json($game->state());
    }

    public function pickUpPile(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        $player = $game->players()->where('user_id', auth()->id())->first();

        if (!$player || !$game->isPlayerTurn($player)) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        $player->pickUpPile($game->pile);

        $game->nextTurn();
        broadcast(new GameUpdated($game))->toOthers();

        return response()->json($game->state());
    }
    public function drawCard(Request $request, $gameId)
    {
        $game = Game::findOrFail($gameId);
        $player = $game->players()->where('user_id', auth()->id())->first();

        if (!$player || !$game->isPlayerTurn($player)) {
            return response()->json(['message' => 'Not your turn.'], 403);
        }

        $player->drawCard($game->deck);

        broadcast(new GameUpdated($game))->toOthers();

        return response()->json($game->state());
    }
}
