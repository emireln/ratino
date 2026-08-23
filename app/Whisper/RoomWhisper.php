<?php namespace App\Whisper;

use App\Models\Room;
use App\Models\User;

class RoomWhisper extends Whisper {

    public function event(): string {
        return 'Room';
    }

    public function process($data): array {
        if ($this->user == null) return ['code' => 1, 'message' => 'Not authorized'];

        $action = $data->action ?? 'list';

        if ($action === 'list') {
            $rooms = Room::where('private', false)->orWhere('members', $this->user->_id)
                ->orderBy('created_at', 'desc')->limit(20)->get();
            return ['rooms' => array_map(function($room) {
                return [
                    'code' => $room->code,
                    'name' => $room->name,
                    'members' => count($room->members ?? [])
                ];
            }, $rooms->toArray())];
        }

        if ($action === 'create') {
            $name = mb_substr(trim($data->name ?? ''), 0, 32);
            if (strlen($name) < 1) return ['code' => 2, 'message' => 'Room name required'];

            $room = Room::create([
                'code' => Room::generateCode(),
                'name' => $name,
                'private' => true,
                'owner' => $this->user->_id,
                'members' => [$this->user->_id]
            ]);

            return ['code' => $room->code, 'name' => $room->name];
        }

        if ($action === 'join') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) return ['code' => 3, 'message' => 'Unknown room code'];

            $members = $room->members ?? [];
            if (!in_array($this->user->_id, $members)) {
                array_push($members, $this->user->_id);
                $room->update(['members' => $members]);
            }

            return ['code' => $room->code, 'name' => $room->name];
        }

        if ($action === 'leave') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) return ['code' => 3, 'message' => 'Unknown room code'];

            $members = array_values(array_filter($room->members ?? [], function($id) {
                return $id !== $this->user->_id;
            }));
            $room->update(['members' => $members]);
            return [];
        }

        return ['code' => 4, 'message' => 'Unknown action'];
    }

}