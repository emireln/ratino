<?php namespace App\Whisper;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class LeaderboardWhisper extends Whisper {

    public function event(): string {
        return 'Leaderboard';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        $orderBy = in_array($data->orderBy ?? 'wager', ['wager', 'multiplier']) ? $data->orderBy : 'wager';
        $period = in_array($data->period ?? 'alltime', ['daily', 'weekly', 'alltime']) ? $data->period : 'alltime';

        $since = null;
        if ($period === 'daily') $since = Carbon::today();
        if ($period === 'weekly') $since = Carbon::now()->startOfWeek();

        $match = [
            'demo' => false,
            'status' => ['$in' => ['win', 'lose']]
        ];
        if ($since != null) $match['created_at'] = ['$gte' => new \MongoDB\BSON\UTCDateTime($since->getTimestamp() * 1000)];

        if ($orderBy === 'wager') {
            $pipeline = [
                ['$match' => $match],
                ['$group' => [
                    '_id' => '$user',
                    'wager' => ['$sum' => '$wager'],
                    'profit' => ['$sum' => '$profit']
                ]],
                ['$sort' => ['wager' => -1]],
                ['$limit' => 10]
            ];
        } else {
            $pipeline = [
                ['$match' => $match],
                ['$sort' => ['multiplier' => -1]],
                ['$limit' => 10],
                ['$project' => [
                    'user' => 1, 'wager' => 1, 'profit' => 1, 'multiplier' => 1, 'game' => 1
                ]]
            ];
        }

        $entries = \App\Models\Game::raw(function($collection) use ($pipeline) {
            return $collection->aggregate($pipeline)->toArray();
        });

        $ids = array_map(function($e) use ($orderBy) {
            $val = $orderBy === 'wager' ? ($e['_id'] ?? null) : ($e['user'] ?? null);
            return $val instanceof \MongoDB\BSON\ObjectId ? (string) $val : strval($val);
        }, $entries);

        $users = [];
        if (count($ids) > 0) {
            foreach (User::whereIn('_id', $ids)->get(['name', 'avatar']) as $u) $users[strval($u->_id)] = $u;
        }

        $result = [];
        foreach ($entries as $i => $e) {
            $rawUid = $orderBy === 'wager' ? ($e['_id'] ?? null) : ($e['user'] ?? null);
            $uid = $rawUid instanceof \MongoDB\BSON\ObjectId ? (string) $rawUid : strval($rawUid);
            $user = $users[$uid] ?? null;
            $result[] = [
                'position' => $i + 1,
                'user' => $user == null ? ['name' => 'Unknown', 'avatar' => '/avatar/unknown'] : $user->toArray(),
                'wager' => round(floatval($e['wager'] ?? 0), 2),
                'profit' => round(floatval($e['profit'] ?? 0), 2),
                'multiplier' => round(floatval($e['multiplier'] ?? 0), 2),
                'game' => $e['game'] ?? null
            ];
        }

        return $result;
    }

}