<?php

namespace App\Token;

/**
 * Plain wrapper around a Sanctum access token so the token model can live
 * on the MongoDB connection without tripping Sanctum's typed constructor.
 */
class NewAccessToken {

    public $accessToken;
    public $plainTextToken;

    public function __construct($accessToken, string $plainTextToken) {
        $this->accessToken = $accessToken;
        $this->plainTextToken = $plainTextToken;
    }

    public function toArray(): array {
        return [
            'accessToken' => $this->accessToken,
            'plainTextToken' => $this->plainTextToken
        ];
    }

    public function __toString(): string {
        return $this->plainTextToken;
    }

}
