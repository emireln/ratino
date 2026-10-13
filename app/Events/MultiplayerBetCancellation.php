<?php

namespace App\Events;

use App\Models\Game;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MultiplayerBetCancellation implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Game $game;
    private User $user;

    public function __construct(Game $game, User $user) {
        $this->game = $game;
        $this->user = $user;
    }

    public function broadcastOn(): Channel {
        return new Channel('multiplayer.'.$this->game->game);
    }

    public function broadcastWith(): array {
        return [
            'game' => $this->game->makeHidden('server_seed')->makeHidden('nonce')->toArray(),
            'user' => $this->user->publicProfile()
        ];
    }

}
