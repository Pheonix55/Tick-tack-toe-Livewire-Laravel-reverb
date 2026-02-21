<?php

use App\Enums\GameStatus;
use App\Enums\GameTurn;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('games', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('player_x_id');
            $table->unsignedBigInteger('player_o_id');
            $table->json('board_state');
            $table->enum('turn', array_map(fn ($c) => $c->value, GameTurn::cases()))
                ->default(GameTurn::X->value);
            $table->enum('status', array_map(fn ($c) => $c->value, GameStatus::cases()))
                ->default(GameStatus::IN_PROGRESS->value);
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->timestamps();

            $table->foreign('player_x_id', 'fk_games_player_x')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('player_o_id', 'fk_games_player_o')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('winner_id', 'fk_games_winner')->references('id')->on('users')->onDelete('set null');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
