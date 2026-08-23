<?php

namespace App\Console\Commands;

use App\Events\ChatMessage;
use App\Models\Chat;
use App\Models\Settings;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;

class Rain extends Command {

    protected $signature = 'win5x:rain';

    protected $description = 'Send rain in chat';

    public function handle() {
        $amount = floatval(Settings::get('rain_amount', '500'));
        if ($amount <= 0) return;

        $users = User::where('last_activity', '>=', now()->subMinutes(3))->get();
        if (count($users) < 2) return;

        $result = [];
        foreach ($users as $user) {
            $user->balance($user->clientCurrency())->add($amount, Transaction::builder()->message('Rain')->get());
            $result[] = ['_id' => $user->_id, 'name' => $user->name, 'avatar' => $user->avatar];
        }

        $message = Chat::create([
            'data' => [
                'users' => $result,
                'reward' => $amount,
                'currency' => 'credits'
            ],
            'type' => 'rain',
            'channel' => 'global'
        ]);

        event(new ChatMessage($message));
        $this->info('Success: ' . count($result) . ' users');
    }

}
