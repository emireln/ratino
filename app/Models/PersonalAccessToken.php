<?php

namespace App\Models;

use Illuminate\Support\Str;
use MongoDB\Laravel\Eloquent\Model;

/**
 * Sanctum's PersonalAccessToken re-implemented on the MongoDB model base so
 * bearer tokens can live in the 'personal_access_tokens' collection.
 */
class PersonalAccessToken extends Model {

    protected $connection = 'mongodb';
    protected $collection = 'personal_access_tokens';

    protected $fillable = [
        'name',
        'token',
        'abilities',
        'expires_at',
    ];

    protected $hidden = [
        'token',
    ];

    protected $casts = [
        'abilities' => 'json',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function tokenable() {
        return $this->morphTo('tokenable');
    }

    public static function findToken($token) {
        if (strpos($token, '|') === false) {
            return static::where('token', hash('sha256', $token))->first();
        }

        [$id, $token] = explode('|', $token, 2);

        if ($instance = static::find($id)) {
            return hash_equals($instance->token, hash('sha256', $token)) ? $instance : null;
        }
    }

    public function can($ability) {
        return in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }

    public function cant($ability) {
        return !$this->can($ability);
    }

}
