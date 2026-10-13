<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsShortName;
use App\Games\Kernel\Game;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiplayerGameFinished implements ShouldBroadcastNow, ShouldRescue {

    use Dispatchable, InteractsWithSockets, SerializesModels, BroadcastsShortName;

    private Game $game;
    private array $data;

    public function __construct(Game $game, array $data) {
        $this->game = $game;
        $this->data = $data;
    }

    public function broadcastOn(): Channel {
        return new Channel('multiplayer.'.$this->game->metadata()->id());
    }

    public function broadcastWith(): array {
        return [
            'game' => $this->game->metadata()->id(),
            'data' => $this->data,
            'client_seed' => $this->game->client_seed(),
            'server_seed' => $this->game->server_seed(),
            'nonce' => $this->game->nonce()
        ];
    }

}
