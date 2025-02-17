<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\Lobby;
use App\Models\LobbyPlayer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    // Get current queue status
    public function status()
    {
        $userId = Auth::id();
        $inQueue = Queue::where('user_id', $userId)->exists();

        return response()->json([
            'in_queue' => $inQueue,
            'message' => $inQueue ? 'In queue' : 'Not in queue',
            'queue_count' => Queue::count(),
            'queue_time' => '0 seconds', // Add logic to calculate queue time if needed
        ]);
    }

    // Get current queue information
    public function index()
    {
        $queueCount = Queue::count(); // Count users in the queue

        return response()->json([
            'queue_count' => $queueCount,
            'queue_time' => '0 seconds', // Add logic to calculate queue time if needed
        ]);
    }

    // Add user to the queue
    public function joinQueue()
    {
        $user = Auth::user();

        // Check if the user is already in the queue
        if (Queue::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already in the queue'], 400);
        }

        // Add the user to the queue
        Queue::create(['user_id' => $user->id]);

        // Check the number of players in the queue
        $queueCount = Queue::count();

        if ($queueCount >= 2) {
            // If there are enough players, create a lobby
            $lobby = Lobby::create(['status' => 'waiting']);

            // Add players to the lobby
            $playersInQueue = Queue::limit(4)->get();
            foreach ($playersInQueue as $queueEntry) {
                LobbyPlayer::create([
                    'lobby_id' => $lobby->id,
                    'user_id' => $queueEntry->user_id,
                    'ready' => false,
                ]);
                $queueEntry->delete(); // Remove from queue
            }

            // Start countdown
            $this->startCountdown($lobby->id);
        }

        return response()->json(['message' => 'Joined the queue successfully']);
    }

    // Remove user from the queue
    public function leaveQueue(Request $request)
    {
        $userId = Auth::id();

        // Find the user in the queue
        $queueEntry = Queue::where('user_id', $userId)->first();
        if ($queueEntry) {
            // Remove the user from the queue
            $queueEntry->delete();
            return response()->json(['message' => 'You left the queue.']);
        }

        return response()->json(['message' => 'You are not in the queue.'], 400);
    }

    private function startCountdown($lobbyId)
    {
        // Simulate a 5-second countdown
        sleep(5);

        // Check if all players are ready
        $lobby = Lobby::find($lobbyId);
        $players = LobbyPlayer::where('lobby_id', $lobbyId)->get();

        $allReady = true;
        foreach ($players as $player) {
            if (!$player->ready) {
                $allReady = false;
                break;
            }
        }

        if ($allReady) {
            // Create the game
            app(GameController::class)->createGameFromLobby($lobby);
        } else {
            // Handle not all players ready case
            $lobby->update(['status' => 'cancelled']);
        }
    }
}