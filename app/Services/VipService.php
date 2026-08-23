<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;
use MongoDB\BSON\Decimal128;

/**
 * VIP Club — 7 tiers, dynamic rakeback on every bet, one-time level-up
 * milestones, real-time progress by total wagered volume.
 */
class VipService {

    public const TIERS = [
        ['name' => 'bronze',  'wager' => 0,        'rakeback' => 0.01, 'milestone' => 5000],
        ['name' => 'silver',  'wager' => 25000,    'rakeback' => 0.015, 'milestone' => 10000],
        ['name' => 'gold',    'wager' => 100000,   'rakeback' => 0.02, 'milestone' => 20000],
        ['name' => 'platinum','wager' => 300000,   'rakeback' => 0.025, 'milestone' => 35000],
        ['name' => 'diamond', 'wager' => 800000,   'rakeback' => 0.03, 'milestone' => 50000],
        ['name' => 'master',  'wager' => 2000000,  'rakeback' => 0.04, 'milestone' => 75000],
        ['name' => 'legend',  'wager' => 5000000,  'rakeback' => 0.05, 'milestone' => 100000]
    ];

    public static function tier(int $index): array {
        return self::TIERS[$index] ?? self::TIERS[count(self::TIERS) - 1];
    }

    public static function tierIndex(float $wager): int {
        $index = 0;
        foreach (self::TIERS as $i => $tier) {
            if ($wager >= $tier['wager']) $index = $i;
        }
        return $index;
    }

    public static function wager(User $user): float {
        $raw = $user->vip_wager ?? new Decimal128('0');
        return $raw instanceof Decimal128 ? floatval($raw->jsonSerialize()['$numberDecimal']) : floatval($raw);
    }

    /**
     * Called on every bet placement (real mode only). Accrues rakeback
     * instantly and pays out tier-up milestones automatically.
     */
    public static function onBet(User $user, float $bet, bool $demo): void {
        if ($demo || $bet <= 0) return;

        $wager = self::wager($user) + $bet;
        $before = self::tierIndex($wager - $bet);
        $after = self::tierIndex($wager);

        $user->update([
            'vip_wager' => new Decimal128(strval($wager)),
            'vip_rakeback' => new Decimal128(strval(self::rakeback($user) + $bet * self::tier($before)['rakeback']))
        ]);

        if ($after > $before) {
            for ($i = $before + 1; $i <= $after; $i++) {
                $tier = self::tier($i);
                if (in_array($tier['name'], $user->vip_milestones ?? [])) continue;

                $milestones = $user->vip_milestones ?? [];
                array_push($milestones, $tier['name']);
                $user->update(['vip_milestones' => $milestones]);

                $user->balance(Currency::find('credits'))->add($tier['milestone'], Transaction::builder()->message('VIP Milestone (' . ucfirst($tier['name']) . ')')->get());
            }
        }
    }

    public static function rakeback(User $user): float {
        $raw = $user->vip_rakeback ?? new Decimal128('0');
        return $raw instanceof Decimal128 ? floatval($raw->jsonSerialize()['$numberDecimal']) : floatval($raw);
    }

    /**
     * Claim accrued rakeback into the player's balance.
     */
    public static function claim(User $user): float {
        $amount = self::rakeback($user);
        if ($amount <= 0) return 0;

        $user->update(['vip_rakeback' => new Decimal128('0')]);
        $user->balance(Currency::find('credits'))->add($amount, Transaction::builder()->message('VIP Rakeback')->get());
        return $amount;
    }

    public static function status(User $user): array {
        $wager = self::wager($user);
        $index = self::tierIndex($wager);
        $tier = self::tier($index);

        $next = $index + 1 < count(self::TIERS) ? self::tier($index + 1) : null;
        $progress = $next == null ? 100 : min(100, max(0, ($wager - $tier['wager']) / ($next['wager'] - $tier['wager']) * 100));

        $milestones = [];
        foreach (self::TIERS as $i => $t) {
            $milestones[] = [
                'name' => $t['name'],
                'reward' => $t['milestone'],
                'reached' => $wager >= $t['wager'],
                'claimed' => in_array($t['name'], $user->vip_milestones ?? [])
            ];
        }

        return [
            'tier' => $index,
            'tierName' => $tier['name'],
            'rakebackRate' => $tier['rakeback'] * 100,
            'wager' => $wager,
            'rakeback' => self::rakeback($user),
            'nextTier' => $next == null ? null : $next['name'],
            'nextWager' => $next == null ? null : $next['wager'],
            'progress' => round($progress, 2),
            'milestones' => $milestones
        ];
    }

}