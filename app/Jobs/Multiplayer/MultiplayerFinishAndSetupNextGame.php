<?php

namespace App\Jobs\Multiplayer;

use App\Events\MultiplayerGameFinished;
use App\Games\Kernel\Game;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class MultiplayerFinishAndSetupNextGame implements ShouldQueue {

    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private Game $game;
    private array $data;
    private $nextGameDelay;

    public function __construct(Game $game, array $data, $nextGameDelay) {
        $this->game = $game;
        $this->data = $data;
        $this->nextGameDelay = $nextGameDelay;
    }

    public function handle(): void {
        event(new MultiplayerGameFinished($this->game, $this->data));

        $this->game->onDispatchedFinish();

        dispatch((new MultiplayerNextGame($this->game))->delay($this->nextGameDelay));
    }

}
