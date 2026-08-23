<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Game extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'games';

    protected $fillable = [
        'id', 'client_seed', 'server_seed', 'nonce', 'user', 'game', 'multiplier', 'status', 'profit', 'data', 'type', 'demo',
        'wager', 'currency'
    ];

    protected $casts = [
        'data' => 'json'
    ];

}
