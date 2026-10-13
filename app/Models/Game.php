<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Game extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'games';

    protected $fillable = [
        'client_seed', 'server_seed', 'nonce', 'user', 'game', 'multiplier', 'status', 'profit', 'data', 'type', 'demo',
        'wager', 'currency', 'settled', 'turn_lock'
    ];

    // No 'json' cast: turn reservation matches data.turn, which needs native BSON, not a JSON string.

}
