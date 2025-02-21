<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\CardPlayed;
use Illuminate\Support\Facades\Log;

class PlayerController extends Controller
{
    public function playCard(Request $request, $gameId)
{
    $user = Auth::user();
    $player = Player::where('user_id', $user->  id)->where('game_id', $gameId)->first();
    $game = Game::findOrFail($gameId);

    if (!$player || $game->current_turn != $player->id) {
        return response()->json(['message' => 'Not your turn or not in game'], 403);
    }

    $cardId = $request->input('card_id');
    $card = Card::find($cardId);

    if (!$card || $card->player_id != $player->id || $card->location != 'hand') {
        return response()->json(['message' => 'Invalid card'], 400);
    }

    $topCard = Card::where('game_id', $gameId)->where('location', 'pile')->orderBy('position', 'desc')->first();
    if ($topCard && !$this->isValidPlay($card, $topCard)) {
        return response()->json(['message' => 'Invalid card play'], 400);
    }

    $pileCount = Card::where('game_id', $gameId)->where('location', 'pile')->count();
    $card->update([
        'player_id' => null,
        'location' => 'pile',
        'position' => $pileCount,
    ]);

    if ($card->value === '10') {
        Card::where('game_id', $gameId)->where('location', 'pile')->delete();
    }

    $handCount = Card::where('player_id', $player->id)->where('location', 'hand')->count();
    if ($handCount < 3) {
        $deckCard = Card::where('game_id', $gameId)->where('location', 'deck')->orderBy('position')->first();
        if ($deckCard) {
            $deckCard->update([
                'player_id' => $player->id,
                'location' => 'hand',
                'position' => $handCount,
            ]);
        }
    }

    $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $player->position)->orderBy('position')->first()
        ?? Player::where('game_id', $gameId)->orderBy('position')->first();
    $game->current_turn = $nextPlayer->id;
    $game->save();

    broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();

    // Trigger bot turn with delay if next player is a bot
    if ($nextPlayer->is_bot) {
        dispatch(function () use ($gameId) {
            $botController = new BotController();
            $botController->playTurn($gameId);
        })->delay(now()->addSeconds(1)); // 1-second delay
    }

    return response()->json(['message' => 'Card played', 'pile' => Card::where('game_id', $gameId)->where('location', 'pile')->get()]);
}
private function isValidPlay($card, $topCard)
{
    // Special cards like '2' are always valid
    if ($card['value'] === '2') return true;

    // '10' burns the pile, always valid
    if ($card['value'] === '10') return true;

    // Normal card check: must be >= top card
    $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
    $cardIndex = array_search($card['value'], $values);
    $topCardIndex = array_search($topCard['value'], $values);

    return $cardIndex >= $topCardIndex;
}


public function pickUpCards(Request $request, $gameId)
{
    try {
        $user = Auth::user();
        $game = Game::findOrFail($gameId);
        $player = Player::where('game_id', $gameId)->where('user_id', $user->id)->first();

        if (!$player) {
            return response()->json(['message' => 'You are not in this game'], 403);
        }

        if ($game->current_turn !== $player->id) {
            return response()->json(['message' => 'Not your turn'], 403);
        }

        $pileCards = Card::where('game_id', $gameId)->where('location', 'pile')->get();

        if ($pileCards->isEmpty()) {
            return response()->json(['message' => 'Pile is empty'], 400);
        }

        foreach ($pileCards as $index => $card) {
            $card->update([
                'location' => 'hand',
                'player_id' => $player->id,
                'position' => Card::where('player_id', $player->id)->where('location', 'hand')->count() + $index,
            ]);
        }

        // Advance turn
        $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $player->position)->orderBy('position')->first()
        ?? Player::where('game_id', $gameId)->orderBy('position')->first();
    $game->current_turn = $nextPlayer->id;
    $game->save();

    broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();

    // Trigger bot turn with delay if next player is a bot
    if ($nextPlayer->is_bot) {
        dispatch(function () use ($gameId) {
            $botController = new BotController();
            $botController->playTurn($gameId);
        })->delay(now()->addSeconds(1)); // 1-second delay
    }
        return response()->json(['success' => true]);
    } catch (\Exception $e) {
        Log::error('Error in pickUpCards: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Server error picking up cards'], 500);
    }
}

public function drawCard($gameId)
{
    try {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
        $game = Game::findOrFail($gameId);

        if (!$player || $game->current_turn != $player->id) {
            return response()->json(['message' => 'Not your turn'], 403);
        }

        $deckCard = Card::where('game_id', $gameId)->where('location', 'deck')->orderBy('position')->first();
        if (!$deckCard) {
            return response()->json(['message' => 'Deck is empty'], 400);
        }

        $handCount = Card::where('player_id', $player->id)->where('location', 'hand')->count();
        $deckCard->update([
            'player_id' => $player->id,
            'location' => 'hand',
            'position' => $handCount,
        ]);

        // Advance turn
        $players = Player::where('game_id', $gameId)->orderBy('position')->get();
        $nextPlayerIndex = ($player->position + 1) % $players->count();
        $game->current_turn = $players[$nextPlayerIndex]->id;
        $game->save();

        event(new CardPlayed($game->id, $deckCard->toArray()));

        return response()->json(['message' => 'Card drawn']);
    } catch (\Exception $e) {
        Log::error('Error in drawCard: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json(['message' => 'Server error drawing card'], 500);
    }
}
}