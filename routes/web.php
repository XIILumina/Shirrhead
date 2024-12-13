<?php

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
    
        // Game creation and playing

    Route::get('/games', [GameController::class, 'index'])->name('games.index');
    Route::post('/game/join', [GameController::class, 'joinGameByCode']);
    Route::post('/game/create', [GameController::class, 'createGame'])->name('game.create');
    Route::get('/game/{game}', [GameController::class, 'viewgame'])->name('game.view');

    // Route::get('/game/{gameId}', function ($gameId) {
    //     return Inertia::render('Game', [
    //         'game_Id' => $gameId
    //     ]);
    // })->name('game.view');

    Route::post('/game/{gameId}/play', [GameController::class, 'playCard'])->name('game.play_card');
    Route::post('/game/{gameId}/pick_up_cards', [GameController::class, 'pickUpCards'])->name('game.pick_up_cards');

    Route::post('/game/{gameId}/player/{playerId}/play', [PlayerController::class, 'playCard'])->name('player.play_card');

    Route::post('/game/{gameId}/player/{playerId}/pick_up_cards', [PlayerController::class, 'pickUpCards'])->name('player.pick_up_cards');

    Route::post('/game/{gameId}/player/{playerId}/draw', [PlayerController::class, 'drawCard'])->name('player.draw_card');
    
    });
    
    require __DIR__ . '/auth.php';
    