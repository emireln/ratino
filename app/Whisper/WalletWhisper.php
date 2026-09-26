<?php namespace App\Whisper;

use App\Currency\Currency;
use Carbon\Carbon;
use MongoDB\BSON\UTCDateTime;

class WalletWhisper extends Whisper {

    public function event(): string {
        return 'Wallet';
    }

    public function process($data): array {
        if ($this->user == null) {
            return ['code' => 1, 'message' => 'Not authorized'];
        }

        $action = $data->action ?? 'info';
        $currency = $this->user->clientCurrency();
        $balance = $this->user->balance($currency);

        if ($action === 'info') {
            $lastWeekly = $this->user->weekly_drop_claimed ? Carbon::parse($this->user->weekly_drop_claimed) : null;
            $canClaimWeekly = $lastWeekly == null || $lastWeekly->diffInDays(Carbon::now()) >= 7;
            $nextWeeklyIn = $canClaimWeekly ? 0 : (7 * 86400 - Carbon::now()->diffInSeconds($lastWeekly));

            return [
                'credits' => $balance->get(),
                'demo_credits' => $this->user->balance($currency)->demo(true)->get(),
                'can_claim_weekly' => $canClaimWeekly,
                'next_weekly_in' => max(0, $nextWeeklyIn),
                'weekly_amount' => 5000,
                'can_claim_faucet' => $balance->get() < 50,
                'faucet_amount' => 500
            ];
        }

        if ($action === 'weekly_drop') {
            $amount = 5000;
            $claimedAt = Carbon::now();
            $cutoff = $claimedAt->copy()->subDays(7);
            $eligible = ['$or' => [
                ['weekly_drop_claimed' => ['$exists' => false]],
                ['weekly_drop_claimed' => null],
                ['weekly_drop_claimed' => ['$lte' => new UTCDateTime($cutoff->getTimestamp() * 1000)]]
            ]];
            if(!$balance->addConditionally($amount, $eligible, [
                'weekly_drop_claimed' => new UTCDateTime($claimedAt->getTimestamp() * 1000)
            ], ['type' => 'weekly_drop', 'note' => 'Weekly Chips Drop'])) {
                return ['code' => 2, 'message' => 'Weekly drop not available yet'];
            }
            $this->user->setAttribute('weekly_drop_claimed', $claimedAt);

            return [
                'success' => true,
                'amount' => $amount,
                'new_balance' => $balance->get(),
                'message' => 'Claimed 5,000 Chips weekly drop!'
            ];
        }

        if ($action === 'faucet') {
            if ($balance->get() >= 50) {
                return ['code' => 3, 'message' => 'Refill only available when balance is below 50 Chips'];
            }

            $amount = 500;
            if(!$balance->addIfBelow($amount, 50, ['type' => 'faucet', 'note' => 'Emergency Chips Refill'])) {
                return ['code' => 3, 'message' => 'Refill only available when balance is below 50 Chips'];
            }

            return [
                'success' => true,
                'amount' => $amount,
                'new_balance' => $balance->get(),
                'message' => 'Added 500 Chips reload!'
            ];
        }

        if ($action === 'reload_demo') {
            $this->user->balance($currency)->demo(true)->add(10000, ['type' => 'demo_reload']);
            return [
                'success' => true,
                'new_demo_balance' => $this->user->balance($currency)->demo(true)->get()
            ];
        }

        return ['code' => 4, 'message' => 'Unknown action'];
    }

}
