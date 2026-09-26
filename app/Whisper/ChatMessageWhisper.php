<?php namespace App\Whisper;

use App\Models\Chat;
use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;

class ChatMessageWhisper extends Whisper {

    public function event(): string {
        return 'ChatMessage';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        $message = mb_substr(trim($data->message ?? ''), 0, 400);
        $channel = mb_substr($data->channel ?? 'global', 0, 32);

        if (strlen($message) < 1) return ['code' => 2, 'message' => 'Empty message'];
        if ($this->user->mute != null && !$this->user->mute->isPast()) return ['code' => 3, 'message' => 'User is muted'];
        if (Chat::where('user_id', $this->user->_id)->where('created_at', '>=', now()->subSeconds(3))->first() != null) return [];

        if (mb_strtolower(mb_substr($message, 0, 5)) === '/tip ') {
            return $this->tip($message);
        }

        Chat::create([
            'user' => [
                '_id' => $this->user->_id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar
            ],
            'user_id' => $this->user->_id,
            'vipLevel' => $this->user->vipLevel(),
            'data' => $message,
            'type' => 'message',
            'channel' => $channel
        ]);

        return [];
    }

    private function tip(string $message): array {
        $parts = preg_split('/\s+/', trim(mb_substr($message, 5)));
        if (count($parts) < 2) return ['code' => 4, 'message' => 'Usage: /tip name amount'];

        $name = ltrim($parts[0], '@');
        $amount = round(floatval($parts[1]), 2);
        if (!is_finite($amount) || $amount <= 0) return ['code' => 4, 'message' => 'Usage: /tip name amount'];

        $target = User::where('name', $name)->first();
        if ($target == null || $target->_id === $this->user->_id) return ['code' => 5, 'message' => 'Unknown user'];

        $currency = Currency::find('credits');
        $balance = $this->user->balance($currency)->get();
        if ($balance < $amount) return ['code' => 6, 'message' => 'Not enough balance'];

        if(!$this->user->balance($currency)->subtract($amount, Transaction::builder()->message('Tip to ' . $target->name)->get())) {
            return ['code' => 6, 'message' => 'Not enough balance'];
        }
        if(!$target->balance($currency)->add($amount, Transaction::builder()->message('Tip from ' . $this->user->name)->get())) {
            $this->user->balance($currency)->add($amount, Transaction::builder()->message('Tip refund')->get());
            return ['code' => 7, 'message' => 'Could not complete tip'];
        }

        Chat::create([
            'user' => [
                '_id' => $this->user->_id,
                'name' => $this->user->name,
                'avatar' => $this->user->avatar
            ],
            'user_id' => $this->user->_id,
            'vipLevel' => $this->user->vipLevel(),
            'data' => [
                'from' => $this->user->name,
                'to' => $target->name,
                'amount' => $amount
            ],
            'type' => 'tip',
            'channel' => 'global'
        ]);

        return [];
    }

}
