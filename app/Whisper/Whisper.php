<?php

namespace App\Whisper;

use App\Models\User;

/**
 * Server-authoritative game/social action handlers. The browser POSTs
 * /api/whisper with {event, data}; responses return over HTTP, broadcast
 * notifications go out over Reverb channels.
 */
abstract class Whisper {

    public ?User $user;
    public string $id;

    public abstract function event(): string;

    public abstract function process($data): array;

    public static function find(string $eventName): ?Whisper {
        $list = self::list();
        return isset($list[$eventName]) ? (new $list[$eventName]) : null;
    }

    public static function list(): array {
        return [
            'Play' => PlayWhisper::class,
            'Turn' => TurnWhisper::class,
            'Finish' => FinishWhisper::class,
            'GameData' => GameDataWhisper::class,
            'RestoreData' => RestoreDataWhisper::class,

            'Vip' => VipWhisper::class,
            'ChatMessage' => ChatMessageWhisper::class,
            'ChatHistory' => ChatHistoryWhisper::class,
            'Rain' => RainWhisper::class,
            'Heist' => HeistWhisper::class,
            'Bounties' => BountiesWhisper::class,
            'Leaderboard' => LeaderboardWhisper::class,
            'LiveFeed' => LiveFeedWhisper::class,
            'Room' => RoomWhisper::class,
            'Wallet' => WalletWhisper::class
        ];
    }

}
