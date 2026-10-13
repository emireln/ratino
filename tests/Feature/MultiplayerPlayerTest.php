<?php

namespace Tests\Feature;

use App\Games\Crash;
use App\Games\Kernel\Multiplayer\MultiplayerGameStateBuilder;
use Tests\TestCase;

class MultiplayerPlayerTest extends TestCase
{
    public function test_one_bet_per_user_per_round(): void
    {
        $state = new MultiplayerGameStateBuilder(new Crash());
        $state->resetPlayers();
        $bet = fn (string $userId) => ['user' => ['_id' => $userId], 'game' => [], 'data' => []];

        try {
            $this->assertTrue($state->addPlayer($bet('user-a')));
            $this->assertFalse($state->addPlayer($bet('user-a')));
            $this->assertTrue($state->addPlayer($bet('user-b')));
            $this->assertCount(2, $state->players());
        } finally {
            $state->resetPlayers();
        }
    }
}
