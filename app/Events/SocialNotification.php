<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SocialNotification implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    public string $recipientId;
    public string $type;
    public array $data;

    public function __construct(string $recipientId, string $type, array $data = []) {
        $this->recipientId = $recipientId;
        $this->type = $type;
        $this->data = $data;
    }

    public function broadcastOn(): Channel {
        return new PrivateChannel('user.' . $this->recipientId);
    }

    public function broadcastWith(): array {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toIso8601String()
        ];
    }

}
