<?php

namespace App\Whisper;

use App\Models\Room;

class RoomWhisper extends Whisper
{
    public function event(): string
    {
        return 'Room';
    }

    public function process($data): array
    {
        if ($this->user == null) {
            return ['code' => 1, 'message' => 'Not authorized'];
        }

        $action = $data->action ?? 'list';

        if ($action === 'list') {
            $rooms = Room::where('private', false)->orWhere('members', $this->user->_id)
                ->orderBy('created_at', 'desc')->limit(20)->get();

            return ['rooms' => $rooms->map(function ($room) {
                return [
                    'code' => $room->code,
                    'name' => $room->name,
                    'members' => count($room->members ?? []),
                    'owner' => strval($room->owner) === strval($this->user->_id),
                ];
            })->toArray()];
        }

        if ($action === 'create') {
            $name = mb_substr(trim($data->name ?? ''), 0, 32);
            if (strlen($name) < 1) {
                return ['code' => 2, 'message' => 'Room name required'];
            }

            $room = Room::create([
                'code' => Room::generateCode(),
                'name' => $name,
                'private' => true,
                'owner' => $this->user->_id,
                'members' => [$this->user->_id],
            ]);

            return ['code' => $room->code, 'name' => $room->name];
        }

        if ($action === 'join') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) {
                return ['code' => 3, 'message' => 'Unknown room code'];
            }

            $members = $room->members ?? [];
            if (! in_array($this->user->_id, $members)) {
                array_push($members, $this->user->_id);
                $room->update(['members' => $members]);
            }

            return ['code' => $room->code, 'name' => $room->name];
        }

        if ($action === 'update') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) {
                return ['code' => 3, 'message' => 'Unknown room code'];
            }
            if (strval($room->owner) !== strval($this->user->_id)) {
                return ['code' => 5, 'message' => 'Only the room owner can edit this room'];
            }

            $name = mb_substr(trim($data->name ?? ''), 0, 32);
            if (strlen($name) < 1) {
                return ['code' => 2, 'message' => 'Room name required'];
            }

            $room->update(['name' => $name]);

            return ['code' => $room->code, 'name' => $room->name];
        }

        if ($action === 'delete') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) {
                return ['code' => 3, 'message' => 'Unknown room code'];
            }
            if (strval($room->owner) !== strval($this->user->_id)) {
                return ['code' => 5, 'message' => 'Only the room owner can delete this room'];
            }

            $room->delete();

            return [];
        }

        if ($action === 'leave') {
            $room = Room::where('code', mb_strtoupper($data->code ?? ''))->first();
            if ($room == null) {
                return ['code' => 3, 'message' => 'Unknown room code'];
            }

            $members = array_values(array_filter($room->members ?? [], function ($id) {
                return $id !== $this->user->_id;
            }));
            $room->update(['members' => $members]);

            return [];
        }

        return ['code' => 4, 'message' => 'Unknown action'];
    }
}
