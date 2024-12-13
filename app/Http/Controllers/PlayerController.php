<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PlayerController extends Controller
{
    // Play a card
    public function playCard(Request $request, $gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();

        if (!$player) {
            return response()->json(['message' => 'You are not part of this game'], 403);
        }

        $playedCards = $request->input('cards');
        $hand = json_decode($player->hand, true);
        $game = Game::find($gameId);

        if (!$game || $game->status !== 'ongoing') {
            return response()->json(['message' => 'Game is not in progress'], 400);
        }

        foreach ($playedCards as $card) {
            $index = array_search($card, $hand);
            if ($index === false) {
                return response()->json(['message' => 'You cannot play this card'], 400);
            }
            unset($hand[$index]); // Remove the card
        }

        $hand = array_values($hand); // Reindex the array
        $player->hand = json_encode($hand);

        // Update the pile in the game state
        $gameCards = json_decode($game->cards, true);
        $gameCards['pile'] = array_merge($gameCards['pile'] ?? [], $playedCards);
        $game->cards = json_encode($gameCards);

        $player->save();
        $game->save();

        // Check if the player won
        if (count($hand) === 0) {
            $game->status = 'completed';
            $game->winner = $user->id;
            $game->save();

            return response()->json(['message' => 'You won the game!'], 200);
        }

        return response()->json(['message' => 'Card(s) played successfully', 'hand' => $player->hand]);
    }

    // Pick up the pile
    public function pickUpCards($gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();

        if (!$player) {
            return response()->json(['message' => 'You are not part of this game'], 403);
        }

        $game = Game::find($gameId);
        $gameCards = json_decode($game->cards, true);

        $pile = $gameCards['pile'] ?? [];
        if (empty($pile)) {
            return response()->json(['message' => 'No cards in the pile to pick up'], 400);
        }

        $playerHand = json_decode($player->hand, true);
        $playerHand = array_merge($playerHand, $pile);

        $player->hand = json_encode($playerHand);
        $gameCards['pile'] = []; // Clear the pile
        $game->cards = json_encode($gameCards);

        $player->save();
        $game->save();

        return response()->json(['message' => 'Picked up all cards from the pile', 'hand' => $player->hand]);
    }

    // Draw a card from the deck
    public function drawCard($gameId)
    {
        $user = Auth::user();
        $player = Player::where('user_id', $user->id)->where('game_id', $gameId)->first();

        if (!$player) {
            return response()->json(['message' => 'You are not part of this game'], 403);
        }

        $game = Game::find($gameId);
        $gameCards = json_decode($game->cards, true);

        $deck = $gameCards['deck'] ?? [];
        if (empty($deck)) {
            return response()->json(['message' => 'No cards left in the deck'], 400);
        }

        $drawnCard = array_shift($deck); // Draw the top card
        $gameCards['deck'] = $deck;

        $playerHand = json_decode($player->hand, true);
        $playerHand[] = $drawnCard;

        $player->hand = json_encode($playerHand);
        $game->cards = json_encode($gameCards);

        $player->save();
        $game->save();

        return response()->json(['message' => 'Card drawn successfully', 'card' => $drawnCard]);
    }
}
