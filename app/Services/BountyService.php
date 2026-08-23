<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;

/**
 * Daily & weekly community bounties. Verified automatically on every
 * finished real game; reward is credited instantly on fulfillment.
 */
class BountyService {

    /**
     * id => [title, reward, target, type] where type is:
     * 'multiplier'  => target = multiplier threshold
     * 'mines'       => target = multiplier, extra mines check (mines >= mines)
     * 'game'        => target = multiplier threshold for a specific game
     * 'streak'      => target = consecutive wins in a game
     * 'wager'       => target = total wager in period
     * 'wins'        => target = total wins in period
     */
    public const DAILY = [
        ['id' => 'daily_mines_50x',   'title' => 'Hit 50x in Mines with 5+ mines',  'reward' => 5000,  'target' => 50, 'type' => 'mines', 'game' => 'mines', 'mines' => 5],
        ['id' => 'daily_crash_100x',  'title' => 'Reach 100x in Crash',             'reward' => 3000,  'target' => 100, 'type' => 'game', 'game' => 'crash'],
        ['id' => 'daily_coinflip_5',  'title' => 'Win 5 consecutive Coinflips',     'reward' => 2000,  'target' => 5, 'type' => 'streak', 'game' => 'coinflip'],
        ['id' => 'daily_dice_99x',    'title' => 'Roll a 99x+ hit in Dice',         'reward' => 1000,  'target' => 99, 'type' => 'game', 'game' => 'dice'],
        ['id' => 'daily_plinko_10x',  'title' => 'Hit 10x in Plinko',               'reward' => 500,   'target' => 10, 'type' => 'game', 'game' => 'plinko'],
        ['id' => 'daily_wager_5000',  'title' => 'Wager 5,000 Chips',            'reward' => 1500,  'target' => 5000, 'type' => 'wager']
    ];

    public const WEEKLY = [
        ['id' => 'weekly_wager_50000', 'title' => 'Wager 50,000 Chips',          'reward' => 10000, 'target' => 50000, 'type' => 'wager'],
        ['id' => 'weekly_any_50x',     'title' => 'Hit any 50x+ multiplier',        'reward' => 5000,  'target' => 50, 'type' => 'multiplier'],
        ['id' => 'weekly_slots_80x',   'title' => 'Hit 80x in Slots',               'reward' => 2500,  'target' => 80, 'type' => 'game', 'game' => 'slots'],
        ['id' => 'weekly_wins_50',     'title' => 'Win 50 games',                   'reward' => 5000,  'target' => 50, 'type' => 'wins']
    ];

    public static function periodKey(string $type): string {
        return $type === 'weekly' ? date('Y-W') : date('Y-m-d');
    }

    public static function defs(string $type): array {
        return $type === 'weekly' ? self::WEEKLY : self::DAILY;
    }

    /**
     * Verify a finished game against every active bounty. Returns ids that
     * were completed and paid out on this call.
     */
    public static function check(User $user, $game): array {
        if ($user == null || $game == null || ($game->demo ?? false) || $game->status === 'cancelled') return [];

        $claimed = [];
        foreach (['daily', 'weekly'] as $type) {
            $period = self::periodKey($type);
            foreach (self::defs($type) as $bounty) {
                $progress = self::progressFor($user, $period, $bounty['id']);

                if ($progress['claimed'] || $progress['current'] >= $bounty['target']) continue;
                if (!self::qualifies($bounty, $game, $user)) continue;

                $newValue = self::valueFor($bounty, $game, $user);
                if ($newValue <= 0) continue;

                $current = $progress['current'] + $newValue;
                if ($current < $bounty['target']) {
                    self::saveProgress($user, $period, $bounty['id'], ['current' => $current, 'claimed' => false]);
                    continue;
                }

                self::saveProgress($user, $period, $bounty['id'], ['current' => $bounty['target'], 'claimed' => true]);
                $user->balance(Currency::find('credits'))->add($bounty['reward'], Transaction::builder()->message('Bounty: ' . $bounty['title'])->get());
                array_push($claimed, $bounty['id']);
            }
        }

        return $claimed;
    }

    private static function qualifies(array $bounty, $game, User $user): bool {
        $isWin = $game->status === 'win';
        switch ($bounty['type']) {
            case 'wager':
                return $game->wager > 0;
            case 'wins':
                return $isWin;
            case 'streak':
                return $game->game === $bounty['game'];
            case 'mines':
                if ($game->game !== $bounty['game']) return false;
                $mines = $game->data['user_data']['data']['mines'] ?? 0;
                return $mines >= ($bounty['mines'] ?? 1);
            case 'game':
                return $game->game === $bounty['game'];
            case 'multiplier':
                return true;
        }
        return false;
    }

    private static function valueFor(array $bounty, $game, User $user): float {
        switch ($bounty['type']) {
            case 'wager':
                return floatval($game->wager);
            case 'wins':
                return $game->status === 'win' ? 1 : 0;
            case 'streak':
                $streaks = $user->streaks ?? [];
                $streak = intval($streaks[$bounty['game']] ?? 0);
                if ($game->status === 'win') {
                    $streak++;
                    $streaks[$bounty['game']] = $streak;
                    $user->update(['streaks' => $streaks]);
                } else {
                    $streaks[$bounty['game']] = 0;
                    $user->update(['streaks' => $streaks]);
                }
                return $streak;
            default:
                return floatval($game->multiplier);
        }
    }

    private static function progressFor(User $user, string $period, string $id): array {
        $bounties = $user->bounties ?? [];
        return $bounties[$period][$id] ?? ['current' => 0, 'claimed' => false];
    }

    private static function saveProgress(User $user, string $period, string $id, array $value): void {
        $bounties = $user->bounties ?? [];
        $bounties[$period][$id] = $value;
        $user->update(['bounties' => $bounties]);
    }

    public static function progress(User $user): array {
        $result = [];
        foreach (['daily', 'weekly'] as $type) {
            $period = self::periodKey($type);
            $list = [];
            foreach (self::defs($type) as $bounty) {
                $p = self::progressFor($user, $period, $bounty['id']);
                $list[] = array_merge($bounty, [
                    'current' => $p['current'],
                    'claimed' => $p['claimed'],
                    'completed' => $p['current'] >= $bounty['target']
                ]);
            }
            $result[$type] = ['period' => $period, 'bounties' => $list];
        }
        return $result;
    }

}