<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LobbyController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DashboardController::class, 'index'])->name('home');
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::post('/lobby', [LobbyController::class, 'store'])->name('lobby.store');
Route::get('/lobby/{lobby:code}', [LobbyController::class, 'show'])->name('lobby.show');
Route::post('/lobby/{lobby:code}/join', [LobbyController::class, 'join'])->name('lobby.join');
Route::post('/lobby/{lobby:code}/teams', [LobbyController::class, 'storeTeam'])->name('lobby.teams.store');
Route::delete('/lobby/{lobby:code}/players', [LobbyController::class, 'destroyPlayer'])->name('lobby.players.destroy');

require __DIR__.'/settings.php';
