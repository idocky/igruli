<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LobbyController;
use App\Http\Controllers\LobbyGameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/lobby', [LobbyController::class, 'store'])->name('lobby.store');
Route::get('/lobby/{lobby:code}', [LobbyController::class, 'show'])->name('lobby.show');
Route::post('/lobby/{lobby:code}/join', [LobbyController::class, 'join'])->name('lobby.join');
Route::post('/lobby/{lobby:code}/teams', [LobbyController::class, 'storeTeam'])->name('lobby.teams.store');
Route::delete('/lobby/{lobby:code}/players', [LobbyController::class, 'destroyPlayer'])->name('lobby.players.destroy');
Route::post('/lobby/{lobby:code}/start', [LobbyController::class, 'start'])->name('lobby.start');
Route::get('/lobby/{lobby:code}/games/{game}', [LobbyGameController::class, 'show'])->name('lobby.games.show');

require __DIR__.'/settings.php';
