<?php

namespace App\Jobs\Multiplayer;

use App\Games\Kernel\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MultiplayerNextGame implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Game $game;

    public function __construct(Game $game) {
        $this->game = $game;
    }

    public function handle(): void {
        $this->game->nextGame();
    }

}
