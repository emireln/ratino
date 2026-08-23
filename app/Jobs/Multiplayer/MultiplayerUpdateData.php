<?php

namespace App\Jobs\Multiplayer;

use App\Games\Kernel\Game;
use App\Games\Kernel\Multiplayer\MultiplayerGameStateBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MultiplayerUpdateData implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Game $game;
    private array $data;

    public function __construct(Game $game, array $data) {
        $this->game = $game;
        $this->data = $data;
    }

    public function handle(): void {
        $state = new MultiplayerGameStateBuilder($this->game);
        $state->data($this->data);
    }

}
