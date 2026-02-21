<?php

namespace App\Events;

use App\Models\Game;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class GameStarted implements ShouldBroadcastNow
{
    public function __construct(public Game $game) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('lobby.'.$this->game->game_lobby_id),
        ];
    }

    public function broadcastWith(): array
    {
        // dd($this->game->game_lobby_id);

        return [
            'game_id' => $this->game->id,
            'player_x_id' => $this->game->player_x_id,
            'player_o_id' => $this->game->player_o_id,
        ];
    }
}
