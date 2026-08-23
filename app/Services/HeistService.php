<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;

/**
 * Daily Sewer Heist — one free spin per 24h per account. Tiered rewards
 * from 100 to a 50,000 $CHEDDAR jackpot. Weights keep the jackpot rare.
 */
class HeistService {

    public const SEGMENTS = [
        ['reward' => 100,   'weight' => 32],
        ['reward' => 250,   'weight' => 24],
        ['reward' => 500,   'weight' => 17],
        ['reward' => 1000,  'weight' => 12],
        ['reward' => 2500,  'weight' => 8],
        ['reward' => 5000,  'weight' => 4.5],
        ['reward' => 10000, 'weight' => 2],
        ['reward' => 50000, 'weight' => 0.5]
    ];

    public static function nextSpinAt(User $user): ?\Carbon\Carbon {
        if ($user->heist_claimed == null) return null;
        $claimed = $user->heist_claimed instanceof \Carbon\Carbon
            ? $user->heist_claimed
            : \Carbon\Carbon::parse($user->heist_claimed);
        return $claimed->addHours(24);
    }

    public static function canSpin(User $user): bool {
        $next = self::nextSpinAt($user);
        return $next == null || $next->isPast();
    }

    public static function secondsUntil(User $user): int {
        $next = self::nextSpinAt($user);
        if ($next == null) return 0;
        return max(0, $next->getTimestamp() - time());
    }

    /**
     * Pick a weighted segment index, credit the reward, store claim time.
     */
    public static function spin(User $user): array {
        if (!self::canSpin($user)) return ['code' => 1, 'message' => 'Already spun today'];

        $total = 0;
        foreach (self::SEGMENTS as $segment) $total += $segment['weight'];
        $roll = mt_rand() / mt_getrandmax() * $total;
        $index = 0;
        foreach (self::SEGMENTS as $i => $segment) {
            $roll -= $segment['weight'];
            if ($roll <= 0) {
                $index = $i;
                break;
            }
        }

        $segment = self::SEGMENTS[$index];
        $user->update(['heist_claimed' => now()]);
        $user->balance(Currency::find('credits'))->add($segment['reward'], Transaction::builder()->message('Daily Sewer Heist')->get());

        return ['index' => $index, 'reward' => $segment['reward']];
    }

}