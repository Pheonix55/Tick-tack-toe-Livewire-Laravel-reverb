<?php

namespace App\Models;

use App\Enums\LobbyStatus;
use Illuminate\Database\Eloquent\Model;

class GameLobby extends Model
{
    protected $fillable = [
        'id',
        'name', // Name of the lobby, e.g. "John's Game"
        'host_id',
        'invitee_id', // (nullable FK to users) secong player invited by the user
        'is_private',
        'password', // (nullable, only if is_private is true)
        'code', // unique code for joining the lobby, e.g. "ABCD1234"
        'status', // (ENUM: 'waiting', 'full', 'closed')
        'can_start_game', // (boolean) whether the host can start the game
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'can_start_game' => 'boolean',
        'status' => LobbyStatus::class,
    ];

    public function host()
    {
        return $this->belongsTo(User::class, 'host_id');
    }

    public function invitee()
    {
        return $this->belongsTo(User::class, 'invitee_id');
    }
}
