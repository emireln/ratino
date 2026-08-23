<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Chat extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'chat_history';

    protected $fillable = [
        'user', 'data', 'type', 'deleted', 'vipLevel', 'channel', 'user_id'
    ];

    protected $casts = [
        'user' => 'json',
        'data' => 'json'
    ];

}
