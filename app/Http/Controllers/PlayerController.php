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
        try {
            $user = Auth::user();
            $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();
            $game = Game::findOrFail($gameId);

            if (!$player || $game->current_turn != $player->id) {
                return response()->json(['message' => 'Not your turn or not in game'], 403);
            }

            $cardId = $request->input('card_id');
            $card = Card::find($cardId);

            // Determine playable location based on remaining cards
            $handCount = Card::where('player_id', $player->id)->where('location', 'hand')->count();
            $visibleCount = Card::where('player_id', $player->id)->where('location', 'visible')->count();
            $playableLocation = $handCount > 0 ? 'hand' : ($visibleCount > 0 ? 'visible' : 'hidden');

            if (!$card || $card->player_id != $player->id || $card->location != $playableLocation) {
                return response()->json(['message' => "Invalid card from {$card->location}, must play from $playableLocation"], 400);
            }

            $topCard = Card::where('game_id', $gameId)->where('location', 'pile')->orderBy('position', 'desc')->first();
            if ($topCard && !$this->isValidPlay($card, $topCard)) {
                // Force pick up the pile if card is lower
                $this->forcePickUpPile($player, $game);
                return response()->json(['message' => 'Card lower than pile top, pile picked up'], 200);
            }

            $pileCount = Card::where('game_id', $gameId)->where('location', 'pile')->count();
            $card->update([
                'player_id' => null,
                'location' => 'pile',
                'position' => $pileCount,
            ]);

            // Special card effects
            if ($card->value === '10') {
                Card::where('game_id', $gameId)->where('location', 'pile')->delete(); // Burn pile
            } elseif ($card->value === '2') {
                Card::where('game_id', $gameId)->where('location', 'pile')->delete(); // Reset pile
            }

            // Check for winner
            if ($this->checkForWinner($player)) {
                $game->status = 'finished';
                $game->winner_id = $player->id;
                $game->save();
                broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();
                return response()->json(['message' => 'You won!', 'winner' => $player->id]);
            }

            // Advance turn
            $nextPlayer = $this->getNextPlayer($game, $player);
            $game->current_turn = $nextPlayer->id;
            $game->save();

            broadcast(new CardPlayed($gameId, $card->toArray()))->toOthers();

            if ($nextPlayer->is_bot) {
                dispatch(function () use ($gameId) {
                    $botController = new BotController();
                    $botController->playTurn($gameId);
                })->delay(now()->addSeconds(1));
            }

            return response()->json(['message' => 'Card played', 'pile' => Card::where('game_id', $gameId)->where('location', 'pile')->get()]);
        } catch (\Exception $e) {
            Log::error('Error in playCard: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Server error playing card'], 500);
        }
    }

    private function isValidPlay($card, $topCard)
    {
        // Special cards
        if (in_array($card->value, ['2', '10'])) return true;

        $values = ['3', '4', '5', '6', '7', '8', '9', '10', 'J', 'Q', 'K', 'A', '2']; // 2 is highest
        $cardIndex = array_search($card->value, $values);
        $topCardIndex = array_search($topCard->value, $values);

        return $cardIndex >= $topCardIndex;
    }

    private function forcePickUpPile($player, $game)
    {
        $pileCards = Card::where('game_id', $game->id)->where('location', 'pile')->get();
        foreach ($pileCards as $index => $card) {
            $card->update([
                'location' => 'hand',
                'player_id' => $player->id,
                'position' => Card::where('player_id', $player->id)->where('location', 'hand')->count() + $index,
            ]);
        }
        $nextPlayer = $this->getNextPlayer($game, $player);
        $game->current_turn = $nextPlayer->id;
        $game->save();
        broadcast(new CardPlayed($game->id, ['action' => 'pile_picked_up']))->toOthers();
    }

    private function checkForWinner($player)
    {
        $totalCards = Card::where('player_id', $player->id)
            ->whereIn('location', ['hand', 'visible', 'hidden'])
            ->count();
        return $totalCards === 0;
    }

    private function getNextPlayer($game, $currentPlayer)
    {
        $players = Player::where('game_id', $game->id)->orderBy('position')->get();
        $nextIndex = ($currentPlayer->position + 1) % $players->count();
        return $players[$nextIndex];
    }

    public function pickUpCards(Request $request, $gameId)
    {
        try {
            $user = Auth::user();
            $game = Game::findOrFail($gameId);
            $player = Player::where('game_id', $gameId)->where('user_id', $user->id)->first();

            if (!$player || $game->current_turn !== $player->id) {
                return response()->json(['message' => 'Not your turn or not in game'], 403);
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

            $nextPlayer = $this->getNextPlayer($game, $player);
            $game->current_turn = $nextPlayer->id;
            $game->save();

            broadcast(new CardPlayed($gameId, ['action' => 'pile_picked_up']))->toOthers();

            if ($nextPlayer->is_bot) {
                dispatch(function () use ($gameId) {
                    $botController = new BotController();
                    $botController->playTurn($gameId);
                })->delay(now()->addSeconds(1));
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

            $handCount = Card::where('player_id', $player->id)->where('location', 'hand')->count();
            if ($handCount >= 3) {
                return response()->json(['message' => 'Hand is full'], 400);
            }

            $deckCard = Card::where('game_id', $gameId)->where('location', 'deck')->orderBy('position')->first();
            if (!$deckCard) {
                return response()->json(['message' => 'Deck is empty'], 400);
            }

            $deckCard->update([
                'player_id' => $player->id,
                'location' => 'hand',
                'position' => $handCount,
            ]);

            $nextPlayer = $this->getNextPlayer($game, $player);
            $game->current_turn = $nextPlayer->id;
            $game->save();

            broadcast(new CardPlayed($game->id, $deckCard->toArray()))->toOthers();

            if ($nextPlayer->is_bot) {
                dispatch(function () use ($gameId) {
                    $botController = new BotController();
                    $botController->playTurn($gameId);
                })->delay(now()->addSeconds(1));
            }

            return response()->json(['message' => 'Card drawn']);
        } catch (\Exception $e) {
            Log::error('Error in drawCard: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['message' => 'Server error drawing card'], 500);
        }
    }
}