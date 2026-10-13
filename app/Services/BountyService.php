<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Currency\Currency;
use Illuminate\Support\Facades\DB;
use MongoDB\Operation\FindOneAndUpdate;

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
        ['id' => 'weekly_wager_10k',    'title' => 'Wager 10,000 Chips',              'reward' => 2500,  'target' => 10000,  'type' => 'wager'],
        ['id' => 'weekly_wager_50k',    'title' => 'Wager 50,000 Chips',              'reward' => 10000, 'target' => 50000,  'type' => 'wager'],
        ['id' => 'weekly_wager_100k',   'title' => 'Wager 100,000 Chips',             'reward' => 25000, 'target' => 100000, 'type' => 'wager'],
        ['id' => 'weekly_wins_25',      'title' => 'Win 25 games',                    'reward' => 3000,  'target' => 25,     'type' => 'wins'],
        ['id' => 'weekly_wins_50',      'title' => 'Win 50 games',                    'reward' => 7500,  'target' => 50,     'type' => 'wins'],
        ['id' => 'weekly_wins_100',     'title' => 'Win 100 games',                   'reward' => 20000, 'target' => 100,    'type' => 'wins'],
        ['id' => 'weekly_any_50x',      'title' => 'Hit any 50x+ multiplier',         'reward' => 5000,  'target' => 50,     'type' => 'multiplier'],
        ['id' => 'weekly_limbo_20x',    'title' => 'Hit 20x in Limbo',                'reward' => 4000,  'target' => 20,     'type' => 'game', 'game' => 'limbo'],
        ['id' => 'weekly_crash_25x',    'title' => 'Reach 25x in Crash',              'reward' => 6000,  'target' => 25,     'type' => 'game', 'game' => 'crash'],
        ['id' => 'weekly_dice_75x',     'title' => 'Roll 75x+ in Dice',               'reward' => 5000,  'target' => 75,     'type' => 'game', 'game' => 'dice'],
        ['id' => 'weekly_mines_30x',    'title' => 'Hit 30x in Mines with 4+ mines',  'reward' => 8000,  'target' => 30,     'type' => 'mines', 'game' => 'mines', 'mines' => 4],
        ['id' => 'weekly_slots_80x',    'title' => 'Hit 80x in Slots',                'reward' => 5000,  'target' => 80,     'type' => 'game', 'game' => 'slots'],
        ['id' => 'weekly_roulette_35x', 'title' => 'Hit 35x straight-up in Roulette', 'reward' => 7500,  'target' => 35,     'type' => 'game', 'game' => 'roulette'],
        ['id' => 'weekly_plinko_25x',   'title' => 'Hit 25x+ in Plinko',              'reward' => 5000,  'target' => 25,     'type' => 'game', 'game' => 'plinko'],
        ['id' => 'weekly_blackjack_3',  'title' => 'Win 3 consecutive Blackjack hands','reward' => 6000, 'target' => 3,     'type' => 'streak', 'game' => 'blackjack'],
        ['id' => 'weekly_coinflip_5',   'title' => 'Win 5 consecutive Coinflips',     'reward' => 5000,  'target' => 5,      'type' => 'streak', 'game' => 'coinflip'],
        ['id' => 'weekly_tower_10x',    'title' => 'Reach 10x+ in Tower',             'reward' => 4500,  'target' => 10,     'type' => 'game', 'game' => 'tower'],
        ['id' => 'weekly_stairs_15x',   'title' => 'Climb 15x+ in Stairs',            'reward' => 5500,  'target' => 15,     'type' => 'game', 'game' => 'stairs']
    ];

    public static function periodKey(string $type): string {
        return $type === 'weekly' ? date('o-W') : date('Y-m-d');
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

        $hasStreakBounty = false;
        foreach(array_merge(self::DAILY, self::WEEKLY) as $bounty) {
            if($bounty['type'] === 'streak' && $bounty['game'] === $game->game) {
                $hasStreakBounty = true;
                break;
            }
        }
        $gameStreak = $hasStreakBounty ? self::updateStreak($user, $game) : null;
        $users = DB::connection('mongodb')->getCollection($user->getTable());
        $claimed = [];
        foreach (['daily', 'weekly'] as $type) {
            $period = self::periodKey($type);
            foreach (self::defs($type) as $bounty) {
                $progress = self::progressFor($user, $period, $bounty['id']);

                if ($progress['claimed']) continue;
                if (!self::qualifies($bounty, $game, $user)) continue;

                $newValue = self::valueFor($bounty, $game, $gameStreak);
                if ($newValue <= 0 && $bounty['type'] !== 'streak') continue;

                $currentPath = 'bounties.' . $period . '.' . $bounty['id'] . '.current';
                $claimedPath = 'bounties.' . $period . '.' . $bounty['id'] . '.claimed';
                $operation = $bounty['type'] === 'streak'
                    ? ['$set' => [$currentPath => $newValue]]
                    : ['$inc' => [$currentPath => $newValue]];
                $updated = $users->findOneAndUpdate(
                    ['_id' => $user->bsonId(), $claimedPath => ['$ne' => true]],
                    $operation,
                    ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
                );
                if($updated === null) continue;

                $bounties = json_decode(json_encode($updated->bounties ?? []), true) ?: [];
                $current = floatval($bounties[$period][$bounty['id']]['current'] ?? 0);
                if($current < $bounty['target']) continue;

                $claim = $users->updateOne(
                    ['_id' => $user->bsonId(), $claimedPath => ['$ne' => true], $currentPath => ['$gte' => $bounty['target']]],
                    ['$set' => [$currentPath => $bounty['target'], $claimedPath => true]]
                );
                if($claim->getMatchedCount() === 0) continue;

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

    private static function valueFor(array $bounty, $game, ?int $gameStreak): float {
        switch ($bounty['type']) {
            case 'wager':
                return floatval($game->wager);
            case 'wins':
                return $game->status === 'win' ? 1 : 0;
            case 'streak':
                return $gameStreak ?? 0;
            default:
                return floatval($game->multiplier);
        }
    }

    private static function progressFor(User $user, string $period, string $id): array {
        $bounties = $user->bounties ?? [];
        return $bounties[$period][$id] ?? ['current' => 0, 'claimed' => false];
    }

    private static function updateStreak(User $user, $game): int {
        $path = 'streaks.' . $game->game;
        $users = DB::connection('mongodb')->getCollection($user->getTable());
        $updated = $users->findOneAndUpdate(
            ['_id' => $user->bsonId()],
            $game->status === 'win' ? ['$inc' => [$path => 1]] : ['$set' => [$path => 0]],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        if($updated === null) return 0;

        $streaks = json_decode(json_encode($updated->streaks ?? []), true) ?: [];
        $user->setAttribute('streaks', $streaks);
        return intval($streaks[$game->game] ?? 0);
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
