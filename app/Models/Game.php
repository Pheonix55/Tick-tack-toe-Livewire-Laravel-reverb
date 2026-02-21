<?php

namespace App\Models;

use App\Enums\GameStatus;
use App\Enums\GameTurn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Game extends Model
{
    protected $fillable = [
        'player_x_id',
        'player_o_id',
        'game_lobby_id',
        'board_state',  // ["X", null, "O", ...]
        'turn', // (ENUM: 'X', 'O')
        'status', // (ENUM: 'in_progress','draw','X_won','O_won')
        'winner_id', // (nullable FK to users)
    ];

    protected $guarded = ['id'];

    protected $attributes = [
        'board_state' => '["", "", "", "", "", "", "", "", ""]',
        'turn' => 'X',
        'status' => 'in_progress',
    ];

    protected $casts = [
        'board_state' => 'array',
        'turn' => GameTurn::class,
        'status' => GameStatus::class,
    ];

    public function playerX(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_x_id');
    }

    public function playerO(): BelongsTo
    {
        return $this->belongsTo(User::class, 'player_o_id');
    }

    public function winner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'winner_id');
    }

    public function lobby(): BelongsTo
    {
        return $this->belongsTo(GameLobby::class, 'game_lobby_id');
    }

    public function moves()
    {
        return $this->hasMany(Move::class);
    }
}
