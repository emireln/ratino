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

class LiveBet implements ShouldBroadcastNow, ShouldRescue {

    use Dispatchable, InteractsWithSockets, SerializesModels, BroadcastsShortName;

    private Game $game;
    private ?User $user;

    public function __construct(Game $game, ?User $user) {
        $this->game = $game;
        $this->user = $user;
    }

    public function broadcastOn(): Channel {
        return new Channel('live');
    }

    public function broadcastWith(): array {
        return [
            'game' => $this->game->game,
            'wager' => floatval($this->game->wager),
            'multiplier' => floatval($this->game->multiplier),
            'profit' => floatval($this->game->profit),
            'status' => $this->game->status,
            'created_at' => $this->game->created_at == null ? null : $this->game->created_at->toIso8601String(),
            'user' => $this->user == null ? null : ['name' => $this->user->name, 'avatar' => $this->user->avatar]
        ];
    }

}
