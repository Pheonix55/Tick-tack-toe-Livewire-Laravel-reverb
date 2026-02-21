<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Move extends Model
{
    protected $fillable = [
        'id',
        'game_id',
        'player_id',
        'position',
        'symbol',
        'created_at',
    ];

    public function game()
    {
        return $this->belongsTo(Game::class);
    }

    public function player()
    {
        return $this->belongsTo(User::class, 'player_id');
    }
}
