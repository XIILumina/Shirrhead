<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Card;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Events\CardPlayed;

class PlayerController extends Controller
{
    public function playCard(Request $request, $gameId)
{
    $user = Auth::user();
    $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
    $game = Game::findOrFail($gameId);

    // Check if it’s the player's turn using player.id instead of user.id
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

    // Move card to pile
    $pileCount = Card::where('game_id', $gameId)->where('location', 'pile')->count();
    $card->update([
        'player_id' => null,
        'location' => 'pile',
        'position' => $pileCount,
    ]);

    // Handle special cards
    if ($card->value === '10') {
        Card::where('game_id', $gameId)->where('location', 'pile')->delete(); // Burn pile
    }

    // Draw card if hand < 3
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

    // Switch turn
    $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $player->position)->orderBy('position')->first()
        ?? Player::where('game_id', $gameId)->orderBy('position')->first();
    $game->current_turn = $nextPlayer->id; // Use player.id consistently
    $game->save();

    broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();

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


    public function pickUpCards($gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
        $game = Game::findOrFail($gameId);

        if ($game->current_turn != $user->id) {
            return response()->json(['message' => 'Not your turn'], 403);
        }

        $pile = Card::where('game_id', $gameId)->where('location', 'pile')->get();
        if ($pile->isEmpty()) {
            return response()->json(['message' => 'Pile is empty'], 400);
        }

        $handCount = Card::where('player_id', $player->id)->where('location', 'hand')->count();
        foreach ($pile as $index => $card) {
            $card->update([
                'player_id' => $player->id,
                'location' => 'hand',
                'position' => $handCount + $index,
            ]);
        }

        $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $player->position)->orderBy('position')->first()
            ?? Player::where('game_id', $gameId)->orderBy('position')->first();
        $game->current_turn = $nextPlayer->user_id ?? $nextPlayer->id;
        $game->save();

        return response()->json(['message' => 'Pile picked up']);
    }

    public function drawCard($gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
        $game = Game::findOrFail($gameId);

        if ($game->current_turn != $user->id) {
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

        return response()->json(['message' => 'Card drawn']);
    }
}