<?php

use App\Http\Controllers\BotController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\LobbyPlayerController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GameController;
use App\Http\Controllers\QueueController;
use App\Http\Controllers\PlayerController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::middleware('auth')->group(function () {
    // Dashboard (Player Home)
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware('verified')->name('dashboard');

    // Profile Routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Queue Routes
    Route::get('/queue/status', [QueueController::class, 'status'])->name('queue.status');
    Route::post('/queue/join', [QueueController::class, 'joinQueue'])->name('queue.join');
    Route::post('/queue/leave', [QueueController::class, 'leaveQueue'])->name('queue.leave');
    Route::get('/queue/start-countdown/{lobbyId}', [QueueController::class, 'startCountdown'])->name('queue.startCountdown');

    // Lobby Routes
    Route::get('/lobby/{inviteCode}', [LobbyController::class, 'viewLobby'])->name('lobby.view');
    Route::get('/lobby/{inviteCode}/fetch', [LobbyController::class, 'fetchLobby'])->name('lobby.fetch');
    Route::post('/lobby/create', [LobbyController::class, 'createLobby'])->name('lobby.create');
    Route::post('/lobby/{inviteCode}/join', [LobbyController::class, 'joinLobby'])->name('lobby.join');
    Route::post('/lobby/{inviteCode}/ready', [LobbyController::class, 'markReady'])->name('lobby.ready');
    Route::post('/lobby/{inviteCode}/unready', [LobbyController::class, 'unready'])->name('lobby.unready');
    Route::post('/lobby/{inviteCode}/start', [LobbyController::class, 'startGame'])->name('lobby.start');
    

    // Game Routes
    Route::prefix('game')->group(function () {
        Route::post('/createSolo', [GameController::class, 'createSoloGame'])->name('game.solo');
        Route::get('/games', [GameController::class, 'index'])->name('games.index');
        Route::post('/join', [GameController::class, 'joinGameByCode'])->name('game.join');
        Route::post('/create', [GameController::class, 'createGame'])->name('game.create');
        Route::get('/{game}', [GameController::class, 'viewgame'])->name('game.view');
        Route::post('/{gameId}/play-card', [PlayerController::class, 'playCard'])->name('player.play_card');
        Route::post('/{gameId}/draw-card', [PlayerController::class, 'drawCard'])->name('player.draw_card');
        Route::post('/{gameId}/pick-up-cards', [PlayerController::class, 'pickUpCards'])->name('player.pick_up_cards');
        Route::get('/{gameId}/state', [GameController::class, 'getGameState'])->name('game.state');

        Route::post('/{gameId}/bot/play-turn', [BotController::class, 'playTurn'])->name('bot.play_turn');
        Route::post('/{gameId}/bot/pick-up-pile', [BotController::class, 'pickUpPile'])->name('bot.pick_up_pile');
        Route::post('/{gameId}/bot/play-random-card', [BotController::class, 'playRandomCard'])->name('bot.play_random_card');
        Route::post('/{gameId}/bot/play-card', [BotController::class, 'playCard'])->name('bot.play_card');
    });
});

require __DIR__ . '/auth.php';