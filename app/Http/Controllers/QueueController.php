<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    // Получить текущую очередь
    public function index()
    {
        $queueCount = Queue::count(); // Подсчитываем количество пользователей в очереди

        // Возвращаем информацию о текущем состоянии очереди
        return response()->json([
            'queue_count' => $queueCount,
            'queue_time' => '0 seconds', // Здесь можно добавить логику для вычисления времени в очереди, если это нужно
        ]);
    }

    // Добавить пользователя в очередь
    public function joinQueue()
    {
        $user = Auth::user();

        // Проверяем, не находится ли пользователь уже в очереди
        if (Queue::where('user_id', $user->id)->exists()) {
            return response()->json(['message' => 'You are already in the queue'], 400);
        }

        // Добавляем пользователя в очередь
        Queue::create(['user_id' => $user->id]);

        // Проверяем количество игроков в очереди
        $queueCount = Queue::count();

        if ($queueCount >= 2) {
            // Если в очереди достаточно людей, создаем игру (это можно улучшить с помощью событий)
            app(GameController::class)->createGame();
        }

        return response()->json(['message' => 'Joined the queue successfully']);
    }

    // Удалить пользователя из очереди
    public function leaveQueue(Request $request)
    {
        $userId = auth()->id();

        // Находим пользователя в очереди
        $queueEntry = Queue::where('user_id', $userId)->first();
        if ($queueEntry) {
            // Удаляем пользователя из очереди
            $queueEntry->delete();
            return response()->json(['message' => 'You left the queue.']);
        }

        return response()->json(['message' => 'You are not in the queue.'], 400);
    }
}
