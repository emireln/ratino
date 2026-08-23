<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class DisabledGame extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'disabled_games';

    protected $fillable = [
        'name'
    ];

}
