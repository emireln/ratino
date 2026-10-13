<?php

namespace Tests\Feature;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Mines;
use App\Models\Game;
use App\Models\User;
use MongoDB\BSON\Decimal128;
use Tests\TestCase;

class ExtendedGameRefundTest extends TestCase
{
    public function test_stake_is_refunded_and_game_cancelled_when_start_fails(): void
    {
        $user = User::create([
            'name' => 'refund' . uniqid(),
            'email' => uniqid('refund') . '@example.test',
            'password' => 'x',
            'client_seed' => 'test',
            'credits' => new Decimal128('100'),
            'demo_credits' => new Decimal128('0'),
        ]);

        $mines = new class extends Mines {
            public function start(Game $game) { throw new \RuntimeException('start failed'); }
        };
        $data = new Data($user, [
            'api_id' => 'mines', 'bet' => 10, 'currency' => 'credits', 'demo' => false, 'quick' => false, 'data' => ['mines' => 3],
        ]);

        try {
            try {
                $mines->process($data);
                $this->fail('start() failure should propagate');
            } catch (\RuntimeException $e) {
                $this->assertSame('start failed', $e->getMessage());
            }

            $this->assertSame(100.0, $user->fresh()->balance(Currency::find('credits'))->get());
            $this->assertSame(0, Game::where('user', (string) $user->_id)->where('status', 'in-progress')->count());
        } finally {
            Game::where('user', (string) $user->_id)->delete();
            $user->delete();
        }
    }
}
