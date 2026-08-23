<?php

namespace App\Events;

use App\Models\Chat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessage implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    private Chat $message;

    public function __construct(Chat $message) {
        $this->message = $message;
    }

    public function broadcastOn(): PresenceChannel {
        return new PresenceChannel('chat.'.($this->message->channel ?? 'global'));
    }

    public function broadcastWith(): array {
        return $this->message->toArray();
    }

}
