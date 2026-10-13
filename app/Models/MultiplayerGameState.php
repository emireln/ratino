<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class MultiplayerGameState extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'multiplayer_game_states';

    protected $fillable = [
        'game', 'history', 'players', 'server_seed', 'client_seed', 'nonce', 'can_bet', 'timestamp', 'data'
    ];

    protected $hidden = [
        '_id',
        'created_at',
        'updated_at'
    ];

    // 'players' stays a native array: addPlayer() appends with a Mongo $push, which needs an array.
    protected $casts = [
        'history' => 'json',
        'data' => 'json'
    ];

}
