<?php

use App\Models\Room;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return $user != null && (string) $user->_id === (string) $id;
});

Broadcast::channel('chat.{channel}', function ($user, $channel) {
    if ($user == null) return false;

    if ($channel === 'global') {
        return ['name' => $user->name, 'avatar' => $user->avatar, 'vipLevel' => $user->vipLevel()];
    }

    $room = Room::where('code', $channel)->first();
    if ($room == null) return false;
    if ($room->private && !in_array(strval($user->_id), $room->members ?? [])) return false;

    return ['name' => $user->name, 'avatar' => $user->avatar, 'vipLevel' => $user->vipLevel()];
});
