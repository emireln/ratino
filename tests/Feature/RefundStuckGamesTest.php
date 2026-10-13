<?php

namespace Tests\Feature;

use App\Currency\Currency;
use App\Models\Game;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\Decimal128;
use Tests\TestCase;

class RefundStuckGamesTest extends TestCase
{
    public function test_lists_without_changes_then_refunds_each_stuck_game_once(): void
    {
        // 90 = 100 minus the 10 stake the legacy bet already took.
        $user = User::create([
            'name' => 'stuck' . uniqid(),
            'email' => uniqid('stuck') . '@example.test',
            'password' => 'x',
            'client_seed' => 'test',
            'credits' => new Decimal128('90'),
            'demo_credits' => new Decimal128('0'),
        ]);
        $inserted = DB::connection('mongodb')->getCollection('games')->insertOne([
            'id' => 9001, 'user' => (string) $user->_id, 'game' => 'mines', 'type' => 'extended', 'wager' => 10.0,
            'status' => 'in-progress', 'settled' => false, 'currency' => 'credits', 'demo' => false,
        ]);
        $gameId = (string) $inserted->getInsertedId();
        $balance = fn () => $user->fresh()->balance(Currency::find('credits'))->get();

        try {
            $this->artisan('game:refund-stuck')->assertSuccessful();
            $this->assertSame(90.0, $balance());
            $this->assertSame('in-progress', Game::where('_id', $gameId)->first()->status);

            $this->artisan('game:refund-stuck --force')->assertSuccessful();
            $this->assertSame(100.0, $balance());
            $this->assertSame('cancelled', Game::where('_id', $gameId)->first()->status);

            $this->artisan('game:refund-stuck --force')->assertSuccessful();
            $this->assertSame(100.0, $balance());
        } finally {
            Game::where('_id', $gameId)->delete();
            $user->delete();
        }
    }
}
