<?php

namespace App\Console\Commands;

use App\Events\MultiplayerGameFinished;
use App\Events\MultiplayerTimerStart;
use App\Games\Kernel\Game;
use App\Games\Kernel\Multiplayer\MultiplayerGame;
use App\Jobs\Multiplayer\MultiplayerFinishAndSetupNextGame;
use Illuminate\Console\Command;

class Chain extends Command {

    protected $signature = 'game:chain {api_id}';

    protected $description = 'Start MultiplayerGame dispatched job chain';

    public function handle() {
        if (strtolower($this->argument('api_id')) === 'all') {
            foreach (Game::list() as $game) {
                if (!($game instanceof MultiplayerGame)) continue;
                $game->startChain();
                $this->info('Started event chain for ' . $game->metadata()->name());
            }
            return;
        }

        $game = Game::find($this->argument('api_id'));

        if ($game == null) {
            $this->error('Unknown game id');
            return;
        }
        if (!($game instanceof MultiplayerGame)) {
            $this->error('Invalid game type');
            return;
        }

        $game->startChain();
        $this->info('Started event chain for ' . $game->metadata()->name());
    }

}
