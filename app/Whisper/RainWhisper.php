<?php namespace App\Whisper;

use App\Models\Chat;
use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;

class RainWhisper extends Whisper {

    public function event(): string {
        return 'Rain';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        $recent = Chat::where('type', 'rain')->orderBy('created_at', 'desc')->limit(10)->get()->toArray();

        $action = $data->action ?? 'status';
        if ($action === 'fund') {
            $amount = round(floatval($data->amount ?? 0), 2);
            if (!is_finite($amount) || $amount <= 0) return ['code' => 2, 'message' => 'Invalid amount'];

            $currency = Currency::find('credits');
            if ($this->user->balance($currency)->get() < $amount) return ['code' => 3, 'message' => 'Not enough balance'];

            $users = User::where('last_activity', '>=', now()->subMinutes(3))
                ->where('_id', '!=', $this->user->_id)->get();
            if (count($users) < 1) return ['code' => 4, 'message' => 'Nobody online to rain on'];

            $totalCents = (int) round($amount * 100);
            $users = $users->shuffle()->take(min(count($users), $totalCents));
            if(!$this->user->balance($currency)->subtract($amount, Transaction::builder()->message('Rain funded')->get())) {
                return ['code' => 3, 'message' => 'Not enough balance'];
            }

            $baseCents = intdiv($totalCents, count($users));
            $extraCents = $totalCents % count($users);
            $share = round($amount / count($users), 2);
            $result = [];
            foreach ($users as $index => $user) {
                $recipientCents = $baseCents + ($index < $extraCents ? 1 : 0);
                $userShare = $recipientCents / 100;
                if($userShare > 0) $user->balance($currency)->add($userShare, Transaction::builder()->message('Rain from ' . $this->user->name)->get());
                $result[] = ['_id' => $user->_id, 'name' => $user->name, 'avatar' => $user->avatar];
            }

            Chat::create([
                'data' => [
                    'users' => $result,
                    'reward' => round($share, 2),
                    'currency' => 'credits',
                    'funder' => $this->user->name
                ],
                'type' => 'rain',
                'channel' => 'global'
            ]);

            return ['funded' => true, 'amount' => $amount, 'share' => round($share, 2)];
        }

        return ['recent' => $recent];
    }

}
