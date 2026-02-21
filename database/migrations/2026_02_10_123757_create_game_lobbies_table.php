<?php

use App\Enums\LobbyStatus;
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
        Schema::create('game_lobbies', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Name of the lobby, e.g. "John's Game"
            $table->foreignId('host_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('invitee_id')->nullable()->constrained('users')->onDelete('set null'); // (nullable FK to users) secong player invited by the user
            $table->boolean('is_private')->default(false);
            $table->string('password')->nullable(); // (nullable, only if is_private is true)
            $table->string('code')->unique();
            $table->enum('status', array_map(fn ($c) => $c->value, LobbyStatus::cases()))->default(LobbyStatus::WAITING->value); // (ENUM: 'waiting', 'full', 'closed')
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('game_lobbies');
    }
};
