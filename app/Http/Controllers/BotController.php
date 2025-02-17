<?php 
namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Player;
use Illuminate\Http\Request;

class BotController extends Controller
{
    public function playTurn($gameId)
    {
        $game = Game::findOrFail($gameId);
        $bots = Player::where('game_id', $gameId)->where('is_bot', true)->get();

        foreach ($bots as $bot) {
            $hand = json_decode($bot->hand, true);
            $gameCards = json_decode($game->cards, true);
            $pile = $gameCards['pile'] ?? [];

            // Check if the bot can play a card
            $canPlay = false;
            foreach ($hand as $card) {
                if ($this->isValidPlay($card, end($pile))) {
                    $canPlay = true;
                    break;
                }
            }

            if (!$canPlay) {
                // Bot cannot play a card, so it picks up the pile
                $this->pickUpPile($bot, $game);
            } else {
                // Bot plays a random valid card
                $this->playRandomCard($bot, $game);
            }
        }

        return response()->json(['message' => 'Bots played their turns']);
    }

    private function pickUpPile($bot, $game)
    {
        $gameCards = json_decode($game->cards, true);
        $pile = $gameCards['pile'] ?? [];

        if (empty($pile)) {
            return; // No cards to pick up
        }

        // Add pile to bot's hand
        $hand = json_decode($bot->hand, true);
        $hand = array_merge($hand, $pile);

        // Clear the pile
        $gameCards['pile'] = [];
        $game->cards = json_encode($gameCards);

        // Save updates
        $bot->hand = json_encode($hand);
        $bot->save();
        $game->save();
    }

    private function playRandomCard($bot, $game)
    {
        $hand = json_decode($bot->hand, true);
        $gameCards = json_decode($game->cards, true);
        $pile = $gameCards['pile'] ?? [];

        // Find a random valid card to play
        foreach ($hand as $card) {
            if ($this->isValidPlay($card, end($pile))) {
                // Play the card
                $this->playCard($bot, $game, $card);
                break;
            }
        }
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

    private function playCard($bot, $game, $card)
    {
        $hand = json_decode($bot->hand, true);
        $gameCards = json_decode($game->cards, true);
        $pile = $gameCards['pile'] ?? [];

        // Remove the card from the bot's hand
        $index = array_search($card, $hand);
        unset($hand[$index]);
        $hand = array_values($hand); // Reindex the array

        // Add the card to the pile
        $pile[] = $card;

        // Update game state
        $gameCards['pile'] = $pile;
        $game->cards = json_encode($gameCards);

        // Save changes
        $bot->hand = json_encode($hand);
        $bot->save();
        $game->save();
    }
}