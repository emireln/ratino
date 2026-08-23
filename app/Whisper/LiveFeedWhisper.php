<?php namespace App\Whisper;

use App\Models\Game;
use App\Models\User;

class LiveFeedWhisper extends Whisper {

    public function event(): string {
        return 'LiveFeed';
    }

    public function process($data): array {
        $games = Game::whereIn('status', ['win', 'lose'])
            ->orderBy('_id', 'desc')
            ->limit(25)
            ->get();

        $ids = [];
        foreach ($games as $game) {
            if ($game->user != null) $ids[] = $game->user;
        }
        $users = [];
        if (count($ids) > 0) {
            foreach (User::whereIn('_id', array_unique($ids))->get() as $u) {
                $users[strval($u->_id)] = $u;
            }
        }

        $result = [];
        foreach ($games as $game) {
            $user = $game->user == null ? null : ($users[strval($game->user)] ?? null);
            $result[] = [
                'game' => $game->game,
                'wager' => floatval($game->wager),
                'multiplier' => floatval($game->multiplier),
                'profit' => floatval($game->profit),
                'status' => $game->status,
                'created_at' => $game->created_at == null ? null : $game->created_at->toIso8601String(),
                'user' => $user == null ? null : ['name' => $user->name, 'avatar' => $user->avatar]
            ];
        }

        return $result;
    }

}