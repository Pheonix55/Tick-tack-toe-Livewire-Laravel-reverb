<?php

// use App\Models\Game;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');
Route::livewire('/game/{game}', 'game-screen')
    ->name('game.screen');
require __DIR__.'/settings.php';
