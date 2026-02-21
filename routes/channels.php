<?php

use App\Models\Game;
use App\Models\GameLobby;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
Broadcast::channel('lobby.{lobbyId}', function ($user, $lobbyId) {
    return
    GameLobby::where('id', $lobbyId)
        ->where(function ($q) use ($user) {
            $q->where('host_id', $user->id)
                ->orWhere('invitee_id', $user->id);
        })->exists();
});
Broadcast::channel('game.{game}', function ($user, Game $game) {
    return $game->player_x_id === $user->id
        || $game->player_o_id === $user->id;
});
