<?php

use App\Enums\GameStatus;
use App\Enums\GameTurn;
use App\Events\GameWon;
use App\Events\MovePlayed;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

new class extends Component {
    public Game $game;
    public User $currentUser;

    public array $board = [];
    public string $turn;
    public ?string $winner = null;

    public int $timeLeft = 10;
    public ?array $winningLine = null;
    public bool $showWinnerModal = false;
    public bool $showMoveHistory = false;

    /* -------------------------------------------------
     |  INTERNAL LOGGER
     |-------------------------------------------------*/
    protected function logStep(string $step, array $extra = []): void
    {
        Log::channel('game')->debug(
            $step,
            array_merge(
                [
                    'game_id' => $this->game->id ?? null,
                    'auth_user' => auth()->id(),
                    'turn' => $this->turn ?? null,
                    'winner' => $this->winner ?? null,
                    'timeLeft' => $this->timeLeft ?? null,
                    'board' => $this->board ?? null,
                ],
                $extra,
            ),
        );
    }

    protected function logGameEvent(string $event, array $extra = []): void
    {
        if ($this->game->status !== GameStatus::IN_PROGRESS) {
            return; // Only log when game is actively playing
        }

        Log::channel('game_moves')->info(
            $event,
            array_merge(
                [
                    'game_id' => $this->game->id,
                    'player_id' => auth()->id(),
                    'symbol' => $this->turn,
                    'winner' => $this->winner,
                ],
                $extra,
            ),
        );
    }

    /* -------------------------------------------------
     |  LIVEWIRE LIFECYCLE HOOKS
     |-------------------------------------------------*/
    public function mount(Game $game): void
    {
        $this->game = $game->load(['playerX', 'playerO']);
        $this->logStep('MOUNT - start');

        $this->authorizeAccess();
        $this->syncState();
        $this->currentUser = auth()->user();

        $this->logStep('MOUNT - completed');
    }

    public function hydrate()
    {
        $this->logStep('HYDRATE');
    }

    public function dehydrate()
    {
        $this->logStep('DEHYDRATE');
    }

    public function updating($name, $value)
    {
        $this->logStep("UPDATING property: {$name}", ['new_value' => $value]);
    }

    public function updated($name, $value)
    {
        $this->logStep("UPDATED property: {$name}", ['new_value' => $value]);
    }

    /* -------------------------------------------------
     |  STATE SYNC
     |-------------------------------------------------*/
    protected function syncState(): void
    {
        $this->logStep('SYNC STATE - before refresh');

        $this->game->refresh();

        $this->board = $this->game->board_state;
        $this->turn = $this->game->turn->value ?? '';
        if (!$this->winner) {
            $this->winner = $this->game->winner;
        }

        $this->logStep('SYNC STATE - after refresh');
    }

    /* -------------------------------------------------
     |  GAME MOVE
     |-------------------------------------------------*/
    public function updateCell(int $row, int $col): void
    {
        $this->syncState();

        if ($this->winner || !$this->isPlayersTurn()) {
            return;
        }

        if (!isset($this->board[$row][$col]) || $this->board[$row][$col] !== '') {
            return;
        }

        DB::transaction(function () use ($row, $col) {
            $symbol = $this->turn;

            $this->board[$row][$col] = $symbol;

            $winnerData = $this->detectWinner();
            $nextTurn = $symbol === 'X' ? 'O' : 'X';

            $this->game->update([
                'board_state' => $this->board,
                'turn' => $winnerData ? GameTurn::None->value : $nextTurn,
                'winner' => $winnerData['symbol'] ?? null,
            ]);
            //     'game_id',
            // 'player_id',
            // 'position',
            // 'symbol',
            $this->game->moves()->create([
                'player_id' => auth()->id(),
                'symbol' => $symbol,
                'position' => $row * 3 + $col,
            ]);

            $this->turn = $winnerData ? GameTurn::None->value : $nextTurn;
            $this->winner = $winnerData['symbol'] ?? null;

            broadcast(new MovePlayed(gameId: $this->game->id, board: $this->board, turn: $this->turn, winner: $winnerData['symbol'] ?? null))->toOthers();

            // 🔥 Fire dedicated winner event
            if ($winnerData) {
                $this->winner = $winnerData['symbol'];
                $this->winningLine = $winnerData;
                $this->turn = $winnerData ? GameTurn::None->value : $nextTurn;
                $this->showWinnerModal = true;
                broadcast(new GameWon(gameId: $this->game->id, winner: $winnerData))->toOthers();
            }
        });

        $this->timeLeft = 10;
    }

    /* -------------------------------------------------
     |  TIMER
     |-------------------------------------------------*/
    public function decrementTimer(): void
    {
        $this->logStep('DECREMENT TIMER - called');

        $this->syncState();

        if ($this->winner) {
            $this->logStep('DECREMENT TIMER - stopped: winner exists');
            return;
        }

        if (!$this->isPlayersTurn()) {
            $this->logStep('DECREMENT TIMER - skipped: not player turn');
            return;
        }

        $this->timeLeft--;
        $this->logStep('DECREMENT TIMER - decremented');

        if ($this->timeLeft <= 0) {
            $this->logStep('DECREMENT TIMER - triggering autoMove');
            $this->autoMove();
        }
    }

    /* -------------------------------------------------
     |  AUTO MOVE
     |-------------------------------------------------*/
    protected function autoMove(): void
    {
        $this->logStep('AUTO MOVE - start');

        $empty = [];

        foreach ($this->board as $r => $row) {
            foreach ($row as $c => $cell) {
                if ($cell === '') {
                    $empty[] = [$r, $c];
                }
            }
        }

        if (empty($empty)) {
            $this->logStep('AUTO MOVE - no empty cells');
            return;
        }

        $random = $empty[array_rand($empty)];
        $this->logStep('AUTO MOVE - selected cell', ['cell' => $random]);

        $this->updateCell($random[0], $random[1]);
    }

    // handle game won event to show alert

    public function getListeners(): array
    {
        if (!$this->game?->id) {
            return [];
        }

        return [
            "echo-private:game.{$this->game->id},GameWon" => 'handleGameWon',
        ];
    }
    public function handleGameWon(array $payload): void
    {
        $this->winner = $payload['winner']['symbol'];
        $this->winningLine = $payload['winner'];
        $this->showWinnerModal = true;
        $this->dispatch('game-won', winner: $payload['winner']);
    }

    /* -------------------------------------------------
     |  HELPERS
     |-------------------------------------------------*/
    protected function authorizeAccess(): void
    {
        $this->logStep('AUTHORIZE ACCESS - checking');

        if ($this->game->player_x_id !== auth()->id() && $this->game->player_o_id !== auth()->id()) {
            $this->logStep('AUTHORIZE ACCESS - failed');
            abort(403);
        }

        $this->logStep('AUTHORIZE ACCESS - success');
    }

    protected function isPlayersTurn(): bool
    {
        $result = ($this->turn === 'X' && $this->game->player_x_id === auth()->id()) || ($this->turn === 'O' && $this->game->player_o_id === auth()->id());

        $this->logStep('IS PLAYERS TURN', ['result' => $result]);

        return $result;
    }
    public function didCurrentUserWin(): bool
    {
        if (!$this->winner) {
            return false;
        }
        return ($this->winner === 'X' && $this->game->player_x_id === auth()->id()) || ($this->winner === 'O' && $this->game->player_o_id === auth()->id());
    }

    protected function emptyBoard(): array
    {
        return [['', '', ''], ['', '', ''], ['', '', '']];
    }

    protected function detectWinner(): ?array
    {
        $b = $this->board;

        // Rows
        for ($i = 0; $i < 3; $i++) {
            if ($b[$i][0] !== '' && $b[$i][0] === $b[$i][1] && $b[$i][1] === $b[$i][2]) {
                return [
                    'symbol' => $b[$i][0],
                    'type' => 'row',
                    'index' => $i,
                ];
            }
        }

        // Columns
        for ($i = 0; $i < 3; $i++) {
            if ($b[0][$i] !== '' && $b[0][$i] === $b[1][$i] && $b[1][$i] === $b[2][$i]) {
                return [
                    'symbol' => $b[0][$i],
                    'type' => 'col',
                    'index' => $i,
                ];
            }
        }

        // Main diagonal
        if ($b[0][0] !== '' && $b[0][0] === $b[1][1] && $b[1][1] === $b[2][2]) {
            return [
                'symbol' => $b[0][0],
                'type' => 'diag',
                'index' => 0,
            ];
        }

        // Anti diagonal
        if ($b[0][2] !== '' && $b[0][2] === $b[1][1] && $b[1][1] === $b[2][0]) {
            return [
                'symbol' => $b[0][2],
                'type' => 'diag',
                'index' => 1,
            ];
        }

        // Check for draw (board full and no winner)
        $isDraw = true;
        foreach ($b as $row) {
            foreach ($row as $cell) {
                if ($cell === '') {
                    $isDraw = false;
                    break 2; // exit both loops
                }
            }
        }

        if ($isDraw) {
            return [
                'symbol' => null,
                'type' => 'draw',
                'index' => null,
            ];
        }

        // No winner, board not full yet
        return null;
    }

    public function leaveGame(): void
    {
        $this->logStep('LEAVE GAME - start');

        $this->game->status = GameStatus::ABANDONED;
        $this->game->save();

        $this->logStep('LEAVE GAME - saved');

        redirect()->route('home');
    }
    public function goToLobby(): void
    {
        $this->logStep('FINISH GAME - start');
        if (!$this->winner) {
            $this->logStep('FINISH GAME - no winner detected, cannot finish game', ['winner' => $this->winner]);
            return;
        }
        if ($this->winner == 'X') {
            $this->game->status = GameStatus::X_WON;
        } elseif ($this->winner == 'O') {
            $this->game->status = GameStatus::O_WON;
        } else {
            $this->game->status = GameStatus::DRAW;
        }
        $this->game->save();

        $this->logStep('FINISH GAME - saved');

        redirect()->route('dashboard');
    }
};
?>
<div wire:poll.1s="decrementTimer" class="min-h-screen flex flex-col items-center justify-center bg-gray-100 p-8">
    <div class="leaveBtn">
        <button wire:click="leaveGame" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            Leave Game
        </button>
    </div>
    <!-- Turn Indicator -->
    <div class="text-center mb-6 text-xl font-semibold text-gray-700">
        @if ($winner)
            Winner: {{ $winner }}
        @else
            <div class="mb-2 text-lg font-semibold text-gray-600">
                {{ $this->isPlayersTurn() ? 'Your Turn Time Left: ' . $timeLeft . 's' : 'Opponent Turn' }}
            </div>
        @endif
    </div>

    <!-- Game Board -->
    <div class="relative grid grid-cols-3 gap-4 bg-white p-6 rounded-xl shadow-lg w-100 h-100">

        @foreach ($board as $rowIndex => $row)
            @foreach ($row as $colIndex => $cell)
                <div wire:key="cell-{{ $rowIndex }}-{{ $colIndex }}"
                    wire:click="updateCell({{ $rowIndex }}, {{ $colIndex }})"
                    class="flex items-center justify-center text-5xl font-bold border border-gray-200 rounded-lg
                        {{ $cell === 'X' ? 'text-blue-500' : ($cell === 'O' ? 'text-red-500' : '') }}
                        {{ !$winner && $cell === '' && $this->isPlayersTurn() ? 'cursor-pointer hover:bg-gray-50' : '' }}">
                    {{ $cell }}
                </div>
            @endforeach
        @endforeach
        @if ($winningLine)
            @php
                $type = $winningLine['type'];
                $index = $winningLine['index'];
            @endphp

            <div class="absolute inset-0 pointer-events-none">
                @if ($type === 'row')
                    <div class="absolute h-1 bg-green-500"
                        style="
                    width: 100%;
                    top: calc(({{ $index }} * 33.333%) + 16.666%);
                ">
                    </div>
                @elseif ($type === 'col')
                    <div class="absolute w-1 bg-green-500"
                        style="
                    height: 100%;
                    left: calc(({{ $index }} * 33.333%) + 16.666%);
                ">
                    </div>
                @elseif ($type === 'diag' && $index === 0)
                    <div class="absolute h-1 bg-green-500 origin-left"
                        style="
                    width: 141%;
                    top: 50%;
                    left: 0;
                    transform: rotate(45deg);
                ">
                    </div>
                @elseif ($type === 'diag' && $index === 1)
                    <div class="absolute h-1 bg-green-500 origin-left"
                        style="
                    width: 141%;
                    top: 50%;
                    left: 0;
                    transform: rotate(-45deg);
                ">
                    </div>
                @endif
            </div>
        @endif

    </div>

    <!-- Players -->
    <div class="mt-6 flex justify-between w-100">
        <div class="flex flex-col items-center">
            <div
                class="w-16 h-16 rounded-full border-2 border-blue-500 flex items-center justify-center text-blue-500 font-bold">
                X</div>
            <span class="mt-2 text-gray-700 font-medium">{{ $game->playerX->name }}</span>
        </div>

        <div class="flex flex-col items-center">
            <div
                class="w-16 h-16 rounded-full border-2 border-red-500 flex items-center justify-center text-red-500 font-bold">
                O</div>
            <span class="mt-2 text-gray-700 font-medium">{{ $game->playerO->name }}</span>
        </div>
    </div>

    @if ($showWinnerModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-sm">

            <div class="bg-white w-[400px] rounded-2xl shadow-2xl p-8 text-center animate-fadeIn">

                {{-- Icon --}}
                <div class="text-6xl mb-4">
                    @if ($this->didCurrentUserWin())
                        🏆
                    @else
                        😢
                    @endif
                </div>

                {{-- Title --}}
                <h2 class="text-2xl font-bold mb-2">
                    @if ($this->didCurrentUserWin())
                        You Won!
                    @else
                        You Lost
                    @endif
                </h2>

                {{-- Subtitle --}}
                <p class="text-gray-600 mb-6">
                    @if ($this->didCurrentUserWin())
                        Congratulations, great move.
                    @else
                        Better luck next round.
                    @endif
                </p>

                {{-- Winner Info --}}
                <div class="mb-6 text-sm text-gray-500">
                    Winner: {{ $winner }}
                </div>

                {{-- Buttons --}}
                <div class="flex gap-4 justify-center">

                    <button wire:click="goToLobby"
                        class="px-4 py-2 bg-gray-800 text-white rounded-lg hover:bg-gray-900 transition">
                        Back to Lobby
                    </button>

                    <button wire:click="$set('showMoveHistory', true)"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        View Moves
                    </button>

                </div>
            </div>
        </div>
    @endif


</div>
