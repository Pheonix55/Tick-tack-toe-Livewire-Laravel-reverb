<?php

use App\Enums\GameTurn;
use App\Enums\LobbyStatus;
use App\Events\GameStarted;
use App\Events\InviteeReadyUpdated;
use App\Models\Game;
use App\Models\GameLobby;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

new class extends Component {
    public string $gameName = '';
    public string $gameCode = '';
    public bool $inviteeReady = false;
    public bool $canStartGame = false;
    public ?GameLobby $activeLobby = null;
    public bool $inLobby = false;

    /*
    |--------------------------------------------------------------------------
    | Lifecycle
    |--------------------------------------------------------------------------
    */

    public function mount(): void
    {
        $this->loadUserLobby();
    }

    protected function loadUserLobby(): void
    {
        $lobby = GameLobby::where(function ($q) {
            $q->where('host_id', auth()->id())->orWhere('invitee_id', auth()->id());
        })
            ->whereIn('status', [LobbyStatus::WAITING, LobbyStatus::ACTIVE])
            ->latest()
            ->first();

        if ($lobby) {
            $this->activeLobby = $lobby;
            $this->inLobby = true;
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Create Lobby
    |--------------------------------------------------------------------------
    */

    public function createGameLobby(): void
    {
        $this->validate([
            'gameName' => 'required|string|min:3|max:50',
        ]);

        // Prevent multiple open lobbies per user
        if ($this->inLobby) {
            return;
        }

        $code = $this->generateUniqueCode();

        $lobby = GameLobby::create([
            'name' => $this->gameName,
            'host_id' => auth()->id(),
            'status' => LobbyStatus::WAITING,
            'code' => $code,
        ]);

        $this->activeLobby = $lobby;
        $this->inLobby = true;
        $this->gameName = '';
        $this->gameCode = $code;

        // Dispatch only ID (not full model)
        $this->dispatch('lobby-created', id: $lobby->id);
    }

    protected function generateUniqueCode(): string
    {
        do {
            $code = Str::upper(Str::random(6));
        } while (GameLobby::where('code', $code)->exists());

        return $code;
    }

    /*
    |--------------------------------------------------------------------------
    | Join Lobby (Single Source of Truth)
    |--------------------------------------------------------------------------
    */

    public function joinGameLobby(): void
    {
        $this->validate([
            'gameCode' => 'required|string|size:6',
        ]);

        if ($this->inLobby) {
            return;
        }

        DB::transaction(function () {
            $lobby = GameLobby::lockForUpdate()->where('code', $this->gameCode)->first();

            if (!$lobby) {
                return;
            }

            // Host trying to rejoin
            if ($lobby->host_id === auth()->id()) {
                $this->activeLobby = $lobby;
                $this->inLobby = true;
                return;
            }

            // Only waiting lobbies can be joined
            if ($lobby->status !== LobbyStatus::WAITING) {
                return;
            }

            // Already full
            if ($lobby->invitee_id !== null) {
                return;
            }

            $lobby->update([
                'invitee_id' => auth()->id(),
                'status' => LobbyStatus::ACTIVE,
            ]);

            $this->activeLobby = $lobby->fresh();
            $this->inLobby = true;

            $this->dispatch('lobby-joined', id: $lobby->id);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Leave Lobby (Recommended)
    |--------------------------------------------------------------------------
    */

    public function leaveLobby(): void
    {
        if (!$this->activeLobby) {
            return;
        }

        DB::transaction(function () {
            $lobby = GameLobby::lockForUpdate()->find($this->activeLobby->id);

            if (!$lobby) {
                return;
            }

            if ($lobby->host_id === auth()->id()) {
                // Host leaving deletes lobby
                $lobby->delete();
            } else {
                // Invitee leaving resets lobby
                $lobby->update([
                    'invitee_id' => null,
                    'status' => LobbyStatus::WAITING,
                ]);
            }
        });

        $this->activeLobby = null;
        $this->inLobby = false;
        $this->gameCode = '';
    }
    public function startGame()
    {
        // dd($this->activeLobby->id);
        $game = Game::create([
            'player_x_id' => $this->activeLobby->host_id,
            'player_o_id' => $this->activeLobby->invitee_id,
            'turn' => GameTurn::X,
            'game_lobby_id' => $this->activeLobby->id,
            'board_state' => [['', '', ''], ['', '', ''], ['', '', '']],
        ]);
        //need to trigger an event that will get the players for this game inside the game screen.
        broadcast(new GameStarted($game))->toOthers();
        return redirect()->route('game.screen', [
            'game' => $game->id,
        ]);
    }

    public function getListeners(): array
    {
        if (!$this->activeLobby?->id) {
            return [];
        }

        return [
            "echo-private:lobby.{$this->activeLobby->id},GameStarted" => 'handleGameStarted',
            "echo-private:lobby.{$this->activeLobby->id},InviteeReadyUpdated" => 'handleInviteeReadyUpdated',
        ];
    }

    public function handleGameStarted($event)
    {
        // dd('event',$event);
        return redirect()->route('game.screen', [
            'game' => $event['game_id'],
        ]);
    }

    public function toggleReady()
    {
        if (!$this->activeLobby) {
            return;
        } else {
            $this->inviteeReady = !$this->inviteeReady;
            $this->activeLobby->update([
                'can_start_game' => $this->inviteeReady,
            ]);
            broadcast(new InviteeReadyUpdated($this->activeLobby->id, $this->inviteeReady))->toOthers();
        }
    }

    public function handleInviteeReadyUpdated($event)
    {
        $this->activeLobby->can_start_game = $event['inviteeReady'];
    }
    /*
    |--------------------------------------------------------------------------
    | Computed Helpers
    |--------------------------------------------------------------------------
    */

    public function getIsHostProperty(): bool
    {
        return $this->activeLobby && $this->activeLobby->host_id === auth()->id();
    }
};
?>


<div x-data="{ showCreate: false, showJoin: false }" class="min-h-screen bg-gray-100 dark:bg-gray-900 flex flex-col items-center p-6">

    <!-- HEADER -->
    <header class="w-full max-w-6xl flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-gray-100">
            Tic-Tac-Toe Lobby
        </h1>

        <div class="flex gap-3 items-center">

            @unless ($inLobby)
                <button @click="showCreate = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    Create Game
                </button>

                <button @click="showJoin = true"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                    Join Game
                </button>
            @endunless

            @if ($inLobby)
                <button wire:click="leaveLobby"
                    class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                    Leave Lobby
                </button>
            @endif

            <span class="text-sm text-gray-500 dark:text-gray-400">
                {{ $inLobby ? 'In Lobby' : 'Not in Lobby' }}
            </span>

        </div>
    </header>


    <!-- MAIN -->
    <main class="w-full max-w-6xl grid grid-cols-3 gap-6">

        <!-- LEFT PANEL -->
        <section class="col-span-2 bg-white dark:bg-gray-800 rounded-xl shadow p-6">

            {{-- NOT IN LOBBY --}}
            @unless ($inLobby)
                <div class="text-center py-16">
                    <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-200">
                        No Active Lobby
                    </h2>
                    <p class="text-gray-500 mt-2">
                        Create or join a game to start playing.
                    </p>
                </div>
            @endunless


            {{-- IN LOBBY --}}
            @if ($inLobby && $activeLobby)

                <div class="space-y-6">

                    <!-- Lobby Header -->
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-100">
                                {{ $activeLobby->name }}
                            </h2>

                            <p class="text-sm text-gray-500 mt-1">
                                Code:
                                <span class="font-mono bg-gray-200 dark:bg-gray-700 px-2 py-1 rounded">
                                    {{ $activeLobby->code }}
                                </span>
                            </p>
                        </div>

                        <span
                            class="px-3 py-1 text-sm rounded-full
                            {{ $activeLobby->status === \App\Enums\LobbyStatus::WAITING
                                ? 'bg-yellow-100 text-yellow-700'
                                : 'bg-green-100 text-green-700' }}">
                            {{ $activeLobby->status->value }}
                        </span>
                    </div>


                    <!-- Players -->
                    <div class="grid grid-cols-2 gap-4">

                        <!-- Host -->
                        <div class="p-4 border rounded-lg">
                            @if ($activeLobby->host->isOnline())
                                <span class="text-green-500">Online</span>
                            @endif
                            <p class="text-sm text-gray-500">Host</p>
                            <p class="font-semibold">
                                {{ $activeLobby->host->name ?? 'Unknown' }}
                            </p>
                        </div>

                        <!-- Invitee -->
                        @if ($activeLobby->invitee)
                            <div class="p-4 border rounded-lg">
                                @if ($activeLobby->invitee->isOnline())
                                    <span class="text-green-500">Online</span>
                                @endif
                                <p class="text-sm text-gray-500">Opponent</p>
                                <p class="font-semibold">
                                    {{ $activeLobby->invitee->name ?? 'Waiting for player...' }}
                                </p>

                                <button type="button" wire:click="toggleReady"
                                    class="text-sm text-blue-600 {{ auth()->id() === $activeLobby->invitee_id ? '' : 'opacity-50 cursor-not-allowed' }}hover:underline"
                                    {{ auth()->id() === $activeLobby->invitee_id ? '' : 'Disabled' }}>
                                    {{ $inviteeReady ? 'Ready' : 'Not Ready' }}
                                </button>
                            </div>
                        @endif
                    </div>


                    {{-- Game Ready --}}
                    @if ($activeLobby->status === \App\Enums\LobbyStatus::ACTIVE)
                        <div class="p-4 bg-green-100 text-green-700 rounded-lg text-center">
                            Both players
                            joined.{{ $inviteeReady ? ' Game ready to start.' : ' Waiting for invitee to ready up' }}
                        </div>
                        @if ($activeLobby->host_id === auth()->id() && $activeLobby->can_start_game)
                            <span wire:click="startGame" class="cursor-pointer text-blue-600 hover:underline ">Start
                                Game</span>
                        @endif

                    @endif

                </div>

            @endif

        </section>


        <!-- RIGHT PANEL -->
        <aside class="bg-white dark:bg-gray-800 rounded-xl shadow p-6">
            <h2 class="text-lg font-semibold mb-2 text-gray-800 dark:text-gray-100">
                Instructions
            </h2>

            <ul class="text-sm text-gray-600 dark:text-gray-300 space-y-2">
                <li>- Create a lobby and share the code.</li>
                <li>- Join using a 6-character code.</li>
                <li>- Game starts when both players join.</li>
                <li>- Only 2 players allowed per lobby.</li>
            </ul>
        </aside>

    </main>


    <!-- CREATE MODAL -->
    <div x-show="showCreate" x-transition
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div @click.away="showCreate = false" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-96 p-6">

            <h2 class="text-xl font-semibold mb-4">
                Create Game
            </h2>

            <form wire:submit.prevent="createGameLobby" class="space-y-4">

                <div>
                    <input wire:model.defer="gameName" type="text" placeholder="Game name"
                        class="w-full border rounded-lg px-3 py-2" />
                    @error('gameName')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showCreate = false" class="px-4 py-2 bg-gray-300 rounded-lg">
                        Cancel
                    </button>

                    <button type="submit" @click="showCreate = false"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg">
                        Create
                    </button>
                </div>

            </form>

        </div>
    </div>


    <!-- JOIN MODAL -->
    <div x-show="showJoin" x-transition
        class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div @click.away="showJoin = false" class="bg-white dark:bg-gray-800 rounded-xl shadow-lg w-96 p-6">

            <h2 class="text-xl font-semibold mb-4">
                Join Game
            </h2>

            <form wire:submit.prevent="joinGameLobby" class="space-y-4">

                <div>
                    <input wire:model.defer="gameCode" type="text" maxlength="6" placeholder="Enter code"
                        class="w-full border rounded-lg px-3 py-2 uppercase" />
                    @error('gameCode')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end gap-2">
                    <button type="button" @click="showJoin = false" class="px-4 py-2 bg-gray-300 rounded-lg">
                        Cancel
                    </button>

                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg">
                        Join
                    </button>
                </div>

            </form>

        </div>
    </div>

</div>
