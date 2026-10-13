<?php

namespace App\Events;

use App\Events\Concerns\BroadcastsShortName;
use App\Models\Chat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Contracts\Broadcasting\ShouldRescue;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChatMessage implements ShouldBroadcastNow, ShouldRescue {

    use Dispatchable, InteractsWithSockets, SerializesModels, BroadcastsShortName;

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
