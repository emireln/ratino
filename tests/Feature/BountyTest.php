<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\BountyService;
use MongoDB\BSON\Decimal128;
use Tests\TestCase;

class BountyTest extends TestCase
{
    public function test_multiplier_bounty_needs_one_big_hit_not_a_sum(): void
    {
        $user = User::create([
            'name' => 'bounty' . uniqid(),
            'email' => uniqid('bounty') . '@example.test',
            'password' => 'x',
            'client_seed' => 'test',
            'credits' => new Decimal128('0'),
            'demo_credits' => new Decimal128('0'),
        ]);

        try {
            $win = fn (float $multiplier) => (object) [
                'demo' => false, 'status' => 'win', 'game' => 'plinko', 'wager' => 10.0, 'multiplier' => $multiplier, 'data' => [],
            ];

            foreach ([4, 4, 4] as $multiplier) {
                BountyService::check($user->fresh(), $win($multiplier));
            }
            $this->assertNotContains('daily_plinko_10x', BountyService::check($user->fresh(), $win(4)));
            $this->assertContains('daily_plinko_10x', BountyService::check($user->fresh(), $win(10)));
        } finally {
            $user->delete();
        }
    }
}
