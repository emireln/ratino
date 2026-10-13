<?php

namespace Tests\Unit;

use App\Games\Baccarat;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class BaccaratTest extends TestCase
{
    /** Indexes into the Baccarat deck: [player1, dealer1, player2, dealer2, then draws]. */
    private function hand(array $indexes): array
    {
        $game = new Baccarat();
        $method = new ReflectionMethod($game, 'getResult');

        return $method->invoke($game, $indexes);
    }

    public function test_banker_third_card_is_scored_when_player_stands(): void
    {
        // Player 5+2 = 7 stands; banker 2+3 = 5 draws a 3 and totals 8.
        $result = $this->hand([4, 1, 1, 2, 2, 0]);

        $this->assertSame(['player' => 7, 'dealer' => 8], $result['score']);
        $this->assertSame('banker', $result['status']);
    }

    public function test_natural_eight_draws_nothing(): void
    {
        // Player 4+4 = 8 (natural); banker 1+1 = 2.
        $result = $this->hand([3, 0, 3, 0, 0, 0]);

        $this->assertSame(['player' => 8, 'dealer' => 2], $result['score']);
        $this->assertSame('player', $result['status']);
    }
}
