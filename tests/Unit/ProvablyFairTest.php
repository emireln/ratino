<?php

namespace Tests\Unit;

use App\Games\Dice;
use App\Games\Kernel\ProvablyFair;
use PHPUnit\Framework\TestCase;

class ProvablyFairTest extends TestCase
{
    /**
     * Vector computed outside PHP with Node's crypto:
     * HMAC-SHA256(server_seed, "client_seed:nonce:round"), first 4 bytes as a fraction.
     */
    public function test_matches_independent_hmac_vector(): void
    {
        $game = new class extends Dice {
            public function server_seed() { return 'd59637d624c8bb20ec4dadcd6b9022b2'; }
            public function client_seed() { return '02f9fb729d0df6fba8f819874fb36c34'; }
            public function nonce() { return 1; }
        };

        $result = (new ProvablyFair($game))->result();

        $this->assertEquals([66], $result->result()); // floor() yields float 66.0
        $this->assertEqualsWithDelta(0.56206798437051475, $result->extractFloat(), 1e-15);
    }
}
