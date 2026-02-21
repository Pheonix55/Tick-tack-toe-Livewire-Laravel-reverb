<?php

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
        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->boolean('can_start_game')->default(false)->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('game_lobbies', function (Blueprint $table) {
            $table->dropColumn('can_start_game');
        });
    }
};
