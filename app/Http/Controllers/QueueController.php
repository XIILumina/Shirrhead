<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function index()
    {
    return response()->json(['queue' => Queue::all()]);
    }


    public function joinQueue()
    {
        $user = Auth::user();

        // Prevent duplicate queue entries
        if (Queue::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already in the queue'], 400);
        }

        Queue::create(['user_id' => $user->id]);

        // Check if enough players are available for a game
        $queueCount = Queue::count();

        if ($queueCount >= 2) {
            // Trigger Game Creation (Can use Event Listeners for better structure)
            app(GameController::class)->createGame();
        }

        return response()->json(['message' => 'Joined the queue successfully']);
    }

    public function leaveQueue(Request $request)
    {
        $userId = auth()->id();
    
        // Remove the user from the queue
        $queueEntry = Queue::where('user_id', $userId)->first();
        if ($queueEntry) {
            $queueEntry->delete();
            return response()->json(['message' => 'You left the queue.']);
        }
    
        return response()->json(['message' => 'You are not in the queue.'], 400);
}
};