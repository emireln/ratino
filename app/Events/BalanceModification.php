<?php

namespace App\Events;

use App\Currency\Currency;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BalanceModification implements ShouldBroadcastNow {

    use Dispatchable, InteractsWithSockets, SerializesModels;

    private User $user;
    private Currency $currency;
    private string $action;
    private float $value;
    private int $delay;
    private bool $demo;

    public function __construct($user, Currency $currency, string $action, bool $demo, float $value, int $delay) {
        $this->user = $user;
        $this->currency = $currency;
        $this->action = $action;
        $this->value = $value;
        $this->delay = $delay;
        $this->demo = $demo;
    }

    public function broadcastOn(): PrivateChannel {
        return new PrivateChannel('user.'.$this->user->_id);
    }

    public function broadcastWith(): array {
        return [
            'diff' => [
                'action' => $this->action,
                'value' => $this->value
            ],
            'currency' => $this->currency->id(),
            'balance' => $this->user->balance($this->currency)->get(),
            'demo_balance' => $this->user->balance($this->currency)->demo()->get(),
            'delay' => $this->delay
        ];
    }

}
