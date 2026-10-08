<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FastReaction implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $channel;
    public string $emoji;
    public ?User $user;

    public function __construct(string $channel, string $emoji, ?User $user) {
        $this->channel = $channel;
        $this->emoji = $emoji;
        $this->user = $user;
    }

    public function broadcastOn(): Channel {
        return new Channel('live');
    }

    public function broadcastWith(): array {
        return [
            'channel' => $this->channel,
            'emoji' => $this->emoji,
            'user' => $this->user == null ? null : [
                'id' => (string) $this->user->_id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar,
                'vipLevel' => $this->user->vipLevel()
            ],
            'timestamp' => now()->toIso8601String()
        ];
    }

}
