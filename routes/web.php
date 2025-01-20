<?php

use App\Http\Controllers\LobbyController;
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
    Route::get('dashboard', function () {
        return Inertia::render('Dashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
        // Queue actions
    Route::get('/queue', [QueueController::class, 'index']);
    Route::post('/queue/join', [QueueController::class, 'joinQueue'])->name('queue.join');
    Route::post('/queue/leave', [QueueController::class, 'leaveQueue'])->name('queue.leave');

    Route::prefix('lobby')->middleware('auth')->group(function () {
    Route::post('{gameId}/mark-ready', [LobbyController::class, 'markReady']);
    Route::post('join', [LobbyController::class, 'joinLobby']);
    });

    Route::prefix('lobby')->middleware('auth')->group(function () {
        Route::post('/create', [LobbyController::class, 'createLobby'])->name('lobby.create');
        Route::get('/{inviteCode}', [LobbyController::class, 'viewLobby'])->name('lobby.view');
        Route::post('/{inviteCode}/join', [LobbyController::class, 'joinLobby']);
        Route::post('/{inviteCode}/ready', [LobbyController::class, 'markReady']);
        Route::post('/{inviteCode}/start', [LobbyController::class, 'startGame']);
    });
    
    
    Route::post('/game/createSolo', [GameController::class, 'createSoloGame'])->name('game.solo');
    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::post('/game/join', [GameController::class, 'joinGameByCode']);
    Route::post('/game/create', [GameController::class, 'createGame'])->name('game.create');
    Route::get('/game/{game}', [GameController::class, 'viewgame'])->name('game.view');

    Route::get('/game/{gameId}/state', [GameController::class, 'getGameState']);
    Route::post('/game/{gameId}/play-card', [PlayerController::class, 'playCard']);
    Route::post('/game/{gameId}/pick-up', [PlayerController::class, 'pickUpCards']);
    Route::post('/game/{gameId}/draw-card', [PlayerController::class, 'drawCard']);


    // Route::post('/game/{gameId}/play', [GameController::class, 'playCard'])->name('game.play_card');
    // Route::post('/game/{gameId}/pick_up_cards', [GameController::class, 'pickUpCards'])->name('game.pick_up_cards');
    // Route::post('/game/{gameId}/player/{playerId}/play', [PlayerController::class, 'playCard'])->name('player.play_card');
    // Route::post('/game/{gameId}/player/{playerId}/pick_up_cards', [PlayerController::class, 'pickUpCards'])->name('player.pick_up_cards');
    // Route::post('/game/{gameId}/player/{playerId}/draw', [PlayerController::class, 'drawCard'])->name('player.draw_card');

    });
    
    require __DIR__ . '/auth.php';
    