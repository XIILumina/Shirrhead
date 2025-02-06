<?php

namespace App\Http\Controllers;

use App\Models\Queue;
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
            // If there are enough players, create a game (you can improve this with events)
            app(GameController::class)->createGame();
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
}