<?php namespace App\Whisper;

use App\Models\User;
use App\Models\Room;
use App\Events\SocialNotification;
use App\Events\FastReaction;

class FriendWhisper extends Whisper {

    public function event(): string {
        return 'Friend';
    }

    public function process($data): array {
        if ($this->user == null) {
            return ['code' => 1, 'message' => 'Not authorized'];
        }

        $action = $data->action ?? 'list';

        if ($action === 'list') {
            $friendIds = $this->user->friends ?? [];
            $requests = $this->user->friend_requests ?? [];

            $friends = [];
            if (!empty($friendIds)) {
                $userModels = User::whereIn('_id', $friendIds)->get();
                foreach ($userModels as $u) {
                    $isOnline = $u->last_activity && $u->last_activity->diffInMinutes(now()) < 5;
                    $friends[] = [
                        'id' => (string) $u->_id,
                        'name' => $u->name,
                        'avatar' => $u->avatar,
                        'vipLevel' => $u->vipLevel(),
                        'online' => (bool) $isOnline,
                        'active_game' => $u->active_game ?? null,
                        'active_room' => $u->active_room ?? null
                    ];
                }
            }

            // Pending incoming requests
            $incoming = [];
            $incomingIds = array_column(array_filter($requests, fn($r) => ($r['type'] ?? '') === 'incoming'), 'from_id');
            if (!empty($incomingIds)) {
                $incomingUsers = User::whereIn('_id', $incomingIds)->get();
                foreach ($incomingUsers as $u) {
                    $incoming[] = [
                        'id' => (string) $u->_id,
                        'name' => $u->name,
                        'avatar' => $u->avatar,
                        'vipLevel' => $u->vipLevel()
                    ];
                }
            }

            return [
                'friends' => $friends,
                'incoming_requests' => $incoming
            ];
        }

        if ($action === 'send_request') {
            $targetUsername = trim($data->username ?? '');
            if (empty($targetUsername)) {
                return ['code' => 2, 'message' => 'Username required'];
            }

            if (strcasecmp($targetUsername, $this->user->name) === 0) {
                return ['code' => 3, 'message' => 'Cannot add yourself as a friend'];
            }

            $target = User::where('name', $targetUsername)->first();
            if ($target == null) {
                return ['code' => 4, 'message' => 'User not found'];
            }

            $myFriends = $this->user->friends ?? [];
            if (in_array((string) $target->_id, $myFriends)) {
                return ['code' => 5, 'message' => 'Already friends with this user'];
            }

            // Add to target user's friend_requests
            $targetRequests = $target->friend_requests ?? [];
            $alreadyRequested = false;
            foreach ($targetRequests as $req) {
                if (($req['from_id'] ?? '') === (string) $this->user->_id) {
                    $alreadyRequested = true;
                    break;
                }
            }

            if (!$alreadyRequested) {
                $targetRequests[] = [
                    'from_id' => (string) $this->user->_id,
                    'from_name' => $this->user->name,
                    'type' => 'incoming',
                    'created_at' => now()->toIso8601String()
                ];
                $target->update(['friend_requests' => $targetRequests]);

                // Dispatch notification to recipient
                event(new SocialNotification((string) $target->_id, 'friend_request', [
                    'from_id' => (string) $this->user->_id,
                    'from_name' => $this->user->name
                ]));
            }

            return ['success' => true, 'message' => 'Friend request sent to ' . $target->name];
        }

        if ($action === 'accept_request') {
            $senderId = $data->user_id ?? '';
            $sender = User::where('_id', $senderId)->first();
            if ($sender == null) {
                return ['code' => 6, 'message' => 'User not found'];
            }

            // Remove from friend_requests
            $myRequests = array_values(array_filter(
                $this->user->friend_requests ?? [],
                fn($r) => ($r['from_id'] ?? '') !== (string) $sender->_id
            ));

            // Add each other to friends
            $myFriends = $this->user->friends ?? [];
            if (!in_array((string) $sender->_id, $myFriends)) {
                $myFriends[] = (string) $sender->_id;
            }

            $senderFriends = $sender->friends ?? [];
            if (!in_array((string) $this->user->_id, $senderFriends)) {
                $senderFriends[] = (string) $this->user->_id;
            }

            $this->user->update(['friend_requests' => $myRequests, 'friends' => $myFriends]);
            $sender->update(['friends' => $senderFriends]);

            // Notify sender
            event(new SocialNotification((string) $sender->_id, 'friend_accepted', [
                'friend_id' => (string) $this->user->_id,
                'friend_name' => $this->user->name
            ]));

            return ['success' => true, 'message' => 'Accepted friend request from ' . $sender->name];
        }

        if ($action === 'reject_request' || $action === 'remove') {
            $targetId = $data->user_id ?? '';
            
            // Remove from requests and friends
            $myRequests = array_values(array_filter(
                $this->user->friend_requests ?? [],
                fn($r) => ($r['from_id'] ?? '') !== $targetId
            ));

            $myFriends = array_values(array_filter(
                $this->user->friends ?? [],
                fn($id) => $id !== $targetId
            ));

            $this->user->update(['friend_requests' => $myRequests, 'friends' => $myFriends]);

            $other = User::where('_id', $targetId)->first();
            if ($other) {
                $otherFriends = array_values(array_filter(
                    $other->friends ?? [],
                    fn($id) => $id !== (string) $this->user->_id
                ));
                $other->update(['friends' => $otherFriends]);
            }

            return ['success' => true];
        }

        if ($action === 'invite_room') {
            $friendId = $data->friend_id ?? '';
            $roomCode = $data->room_code ?? '';
            $roomName = $data->room_name ?? 'Game Room';

            if (empty($friendId) || empty($roomCode)) {
                return ['code' => 7, 'message' => 'Friend ID and Room Code required'];
            }

            event(new SocialNotification($friendId, 'room_invite', [
                'inviter_name' => $this->user->name,
                'room_code' => $roomCode,
                'room_name' => $roomName
            ]));

            return ['success' => true, 'message' => 'Invitation sent!'];
        }

        if ($action === 'toggle_favorite') {
            $gameId = $data->game ?? '';
            if (empty($gameId)) return ['code' => 9, 'message' => 'Game ID required'];

            $favs = $this->user->favoriteGames ?? [];
            if (!is_array($favs)) $favs = [];

            if (in_array($gameId, $favs)) {
                $favs = array_values(array_filter($favs, fn($g) => $g !== $gameId));
                $added = false;
            } else {
                $favs[] = $gameId;
                $added = true;
            }

            $this->user->update(['favoriteGames' => $favs]);
            return ['success' => true, 'favorites' => $favs, 'added' => $added];
        }

        if ($action === 'get_favorites') {
            return ['favorites' => $this->user->favoriteGames ?? []];
        }

        if ($action === 'react') {
            $emoji = $data->emoji ?? '';
            $channel = mb_substr(trim($data->channel ?? 'live'), 0, 64);
            $allowed = ['🔥', '🚀', '💎', '🤑', '👏', '💀', '🤯', '❤️'];
            if (!in_array($emoji, $allowed, true)) {
                return ['code' => 10, 'message' => 'Unknown reaction'];
            }
            if ($channel === '') $channel = 'live';
            event(new FastReaction($channel, $emoji, $this->user));
            return ['success' => true];
        }

        return ['code' => 8, 'message' => 'Unknown action'];
    }

}
