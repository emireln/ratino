<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsShortName;
use App\Models\Game;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiplayerGameBet implements ShouldBroadcastNow, ShouldRescue {

    use Dispatchable, InteractsWithSockets, SerializesModels, BroadcastsShortName;

    private User $user;
    private Game $game;
    private array $data;

    public function __construct(User $user, Game $game, array $data) {
        $this->user = $user;
        $this->game = $game;
        $this->data = $data;
    }

    public function broadcastOn(): Channel {
        return new Channel('multiplayer.'.$this->game->game);
    }

    public function broadcastWith(): array {
        return [
            'user' => $this->user->publicProfile(),
            'game' => $this->game->makeHidden('server_seed')->makeHidden('nonce')->toArray(),
            'data' => $this->data
        ];
    }

}
