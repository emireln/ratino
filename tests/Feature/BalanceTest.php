<?php

namespace Tests\Feature;

use App\Currency\Currency;
use App\Models\User;
use MongoDB\BSON\Decimal128;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    private function newUser(): User
    {
        return User::create([
            'name' => 'bal' . uniqid(),
            'email' => uniqid('bal') . '@example.test',
            'password' => 'x',
            'client_seed' => 'test',
            'credits' => new Decimal128('100'),
            'demo_credits' => new Decimal128('0'),
        ]);
    }

    public function test_subtract_and_add_persist_on_the_user_document(): void
    {
        $user = $this->newUser();

        try {
            $balance = $user->balance(Currency::find('credits'));
            $this->assertTrue($balance->subtract(30));
            $this->assertTrue($user->balance(Currency::find('credits'))->add(5));

            $this->assertSame(75.0, $user->fresh()->balance(Currency::find('credits'))->get());
        } finally {
            $user->delete();
        }
    }

    public function test_subtract_refuses_to_overdraw(): void
    {
        $user = $this->newUser();

        try {
            $this->assertFalse($user->balance(Currency::find('credits'))->subtract(1000));
            $this->assertSame(100.0, $user->fresh()->balance(Currency::find('credits'))->get());
        } finally {
            $user->delete();
        }
    }
}
