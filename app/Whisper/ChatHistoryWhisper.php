<?php namespace App\Whisper;

use App\Models\Chat;
use App\Models\Room;
use App\Models\User;

class ChatHistoryWhisper extends Whisper {

    public function event(): string {
        return 'ChatHistory';
    }

    public function process($data): array {
        $channel = mb_substr($data->channel ?? 'global', 0, 32);

        if ($channel !== 'global') {
            $room = Room::where('code', $channel)->first();
            if ($room == null) return ['code' => 1, 'message' => 'Unknown room'];
            if ($room->private && ($this->user == null || !in_array($this->user->_id, $room->members ?? []))) return ['code' => 2, 'message' => 'Not a member of this room'];
        }

        // presence heartbeat
        if ($this->user != null) $this->user->update(['last_activity' => now()]);

        $messages = Chat::where('channel', $channel)->orderBy('created_at', 'desc')->limit(60)->get()->reverse()->values();

        $online = [];
        if ($this->user != null) {
            $users = User::where('last_activity', '>=', now()->subSeconds(90))
                ->where('_id', '!=', $this->user->_id)
                ->get(['name', 'avatar', 'vip_wager']);
            foreach ($users as $u) {
                $online[] = [
                    '_id' => $u->_id,
                    'name' => $u->name,
                    'avatar' => $u->avatar,
                    'vipLevel' => $u->vipLevel()
                ];
            }
        }

        return [
            'messages' => $messages->toArray(),
            'online' => $online
        ];
    }

}