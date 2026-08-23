<?php

namespace App\Jobs\Multiplayer;

use App\Games\Kernel\Game;
use App\Games\Kernel\Multiplayer\MultiplayerGameStateBuilder;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MultiplayerDisableBetAccepting implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Game $game;

    public function __construct(Game $game) {
        $this->game = $game;
    }

    public function handle(): void {
        $state = new MultiplayerGameStateBuilder($this->game);
        $state->betting(false);
    }

}
