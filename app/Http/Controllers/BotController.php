<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use App\Models\Card;
use Illuminate\Http\Request;
use App\Events\CardPlayed;

class BotController extends Controller
{
    public function playTurn($gameId)
    {
        $game = Game::findOrFail($gameId);
        $bot = Player::where('game_id', $gameId)->where('id', $game->current_turn)->where('is_bot', true)->first();

        if (!$bot) {
            return response()->json(['message' => 'No bot turn'], 400);
        }

        $hand = Card::where('player_id', $bot->id)->where('location', 'hand')->orderBy('position')->get();
        $pileTop = Card::where('game_id', $gameId)->where('location', 'pile')->orderBy('position', 'desc')->first();

        foreach ($hand as $card) {
            if (!$pileTop || $this->isValidPlay($card, $pileTop)) {
                return $this->playCard($gameId, $card->id);
            }
        }

        return $this->pickUpPile($gameId);
    }

    public function playCard($gameId, $cardId)
    {
        $game = Game::findOrFail($gameId);
        $bot = Player::where('game_id', $gameId)->where('id', $game->current_turn)->where('is_bot', true)->first();
        $card = Card::find($cardId);

        if (!$bot || !$card || $card->player_id != $bot->id || $card->location != 'hand') {
            return response()->json(['message' => 'Invalid bot card play'], 400);
        }

        $topCard = Card::where('game_id', $gameId)->where('location', 'pile')->orderBy('position', 'desc')->first();
        if ($topCard && !$this->isValidPlay($card, $topCard)) {
            return response()->json(['message' => 'Invalid bot card play'], 400);
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

        $handCount = Card::where('player_id', $bot->id)->where('location', 'hand')->count();
        if ($handCount < 3) {
            $deckCard = Card::where('game_id', $gameId)->where('location', 'deck')->orderBy('position')->first();
            if ($deckCard) {
                $deckCard->update([
                    'player_id' => $bot->id,
                    'location' => 'hand',
                    'position' => $handCount,
                ]);
            }
        }

        $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $bot->position)->orderBy('position')->first()
            ?? Player::where('game_id', $gameId)->orderBy('position')->first();
        $game->current_turn = $nextPlayer->id;
        $game->save();

        broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();

        // Chain to next bot with delay
        if ($nextPlayer->is_bot) {
            dispatch(function () use ($gameId) {
                $this->playTurn($gameId);
            })->delay(now()->addSeconds(1));
        }

        return response()->json(['message' => 'Bot played card']);
    }

    private function isValidPlay($card, $topCard)
    {
        if (in_array($card->value, ['2', '10'])) return true;
        $values = ['2', '3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A'];
        return array_search($card->value, $values) >= array_search($topCard->value, $values);
    }


    public function pickUpPile($gameId)
    {
        $game = Game::findOrFail($gameId);
        $bot = Player::where('game_id', $gameId)->where('id', $game->current_turn)->where('is_bot', true)->first();

        if (!$bot) {
            return response()->json(['message' => 'No bot turn'], 400);
        }

        $pile = Card::where('game_id', $gameId)->where('location', 'pile')->get();
        if ($pile->isEmpty()) {
            return response()->json(['message' => 'Pile is empty'], 400);
        }

        $handCount = Card::where('player_id', $bot->id)->where('location', 'hand')->count();
        foreach ($pile as $index => $card) {
            $card->update([
                'player_id' => $bot->id,
                'location' => 'hand',
                'position' => $handCount + $index,
            ]);
        }

        $nextPlayer = Player::where('game_id', $gameId)->where('position', '>', $bot->position)->orderBy('position')->first()
            ?? Player::where('game_id', $gameId)->orderBy('position')->first();
        $game->current_turn = $nextPlayer->id;
        $game->save();

        return response()->json(['message' => 'Bot picked up pile']);
    }

}