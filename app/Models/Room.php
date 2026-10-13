<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

/**
 * Multiplayer rooms. A room is a chat channel: 'global' is the public room,
 * every other room is private and joined by its 5-letter code.
 */
class Room extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'rooms';

    protected $fillable = [
        'code', 'name', 'private', 'owner', 'members', 'created_at'
    ];

    // No 'json' cast: membership queries match members as an array in Mongo.

    public static function generateCode(): string {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        do {
            $code = '';
            for ($i = 0; $i < 5; $i++) $code .= $alphabet[mt_rand(0, strlen($alphabet) - 1)];
        } while (Room::where('code', $code)->first() != null);
        return $code;
    }

}
