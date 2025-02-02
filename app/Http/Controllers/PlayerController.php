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

        $playedCards = $request->input('cards'); // Cards the player is trying to play
        $hand = json_decode($player->hand, true);
        $game = Game::find($gameId);

        if (!$game || $game->status !== 'ongoing') {
            return response()->json(['message' => 'Game is not in progress'], 400);
        }

        $gameCards = json_decode($game->cards, true);
        $pile = $gameCards['pile'] ?? [];

        // Validate the played cards
        foreach ($playedCards as $card) {
            if (!in_array($card, $hand)) {
                return response()->json(['message' => 'You cannot play a card you do not have'], 400);
            }

            // Check if card is valid compared to the pile top
            $topCard = end($pile); // Get the top card
            if ($topCard && !$this->isValidPlay($card, $topCard)) {
                return response()->json(['message' => 'Invalid card play'], 400);
            }
        }

        // Remove played cards from hand
        foreach ($playedCards as $card) {
            $index = array_search($card, $hand);
            unset($hand[$index]);
        }
        $hand = array_values($hand); // Reindex the array

        // Update the pile
        $pile = array_merge($pile, $playedCards);

        // Special rule: Handle special cards like 2 and 10
        foreach ($playedCards as $card) {
            if ($card['value'] === '10') {
                $pile = []; // Burn the pile
                break;
            } elseif ($card['value'] === '2') {
                break; // Reset allows any card next
            }
        }

        // Update game state
        $gameCards['pile'] = $pile;
        $gameCards['deck'] = $gameCards['deck'] ?? [];
        $game->cards = json_encode($gameCards);

        // Draw cards to maintain 3 cards in hand
        while (count($hand) < 3 && !empty($gameCards['deck'])) {
            $hand[] = array_shift($gameCards['deck']);
        }

        // Save changes
        $player->hand = json_encode($hand);
        $game->cards = json_encode($gameCards);
        $player->save();
        $game->save();

        // Check for win condition
        if (count($hand) === 0) {
            $game->status = 'completed';
            $game->winner = $user->id;
            $game->save();
            return response()->json(['message' => 'You won the game!'], 200);
        }

        return response()->json(['message' => 'Card(s) played successfully', 'hand' => $player->hand]);
    }

    // Helper to validate card play
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

        // Add pile to player's hand
        $hand = json_decode($player->hand, true);
        $hand = array_merge($hand, $pile);

        // Clear the pile
        $gameCards['pile'] = [];
        $game->cards = json_encode($gameCards);

        // Save updates
        $player->hand = json_encode($hand);
        $player->save();
        $game->save();

        return response()->json(['message' => 'Picked up all cards from the pile', 'hand' => $player->hand]);
    }

    // Draw a card
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

        // Draw the top card
        $drawnCard = array_shift($deck);
        $hand = json_decode($player->hand, true);
        $hand[] = $drawnCard;

        // Save updates
        $gameCards['deck'] = $deck;
        $game->cards = json_encode($gameCards);
        $player->hand = json_encode($hand);

        $game->save();
        $player->save();

        return response()->json(['message' => 'Card drawn successfully', 'card' => $drawnCard, 'hand' => $player->hand]);
    }
}
