<?php

namespace Tests\Unit;

use App\Games\Dice;
use App\Games\Kernel\Data;
use App\Games\Kernel\ProvablyFair;
use PHPUnit\Framework\TestCase;

class DiceTest extends TestCase
{
    /**
     * Every roll 10..110 is equally likely. With payout 99/winning-rolls the return per bet is
     * 99/101 for every target and value, so the house keeps the same edge on every bet.
     */
    public function test_expected_return_is_99_over_101_for_every_target(): void
    {
        $game = $this->seededDice();
        $seeds = $this->seedForEachRoll($game);

        foreach ([['lower', 10], ['lower', 20], ['lower', 50], ['lower', 110], ['higher', 10], ['higher', 50], ['higher', 110]] as [$target, $value]) {
            $returned = 0.0;
            foreach ($seeds as $seed) {
                $game->seed = $seed;
                $returned += $game->start(null, $this->bet($target, $value))->profit() / 10;
            }

            $this->assertEqualsWithDelta(99 / 101, $returned / 101, 0.001, "{$target} {$value}");
        }
    }

    public function test_lower_twenty_pays_99_over_eleven_winning_rolls(): void
    {
        $game = $this->seededDice();
        $game->seed = $this->seedForEachRoll($game)[15];

        $this->assertEqualsWithDelta(90.0, $game->start(null, $this->bet('lower', 20))->profit(), 0.001);
    }

    private function seededDice(): object
    {
        return new class extends Dice {
            public string $seed = '';
            public function server_seed() { return $this->seed; }
            public function client_seed() { return 'dice-test'; }
            public function nonce() { return 1; }
        };
    }

    /** @return array<int, string> one server seed per roll value 10..110 */
    private function seedForEachRoll(object $game): array
    {
        $seeds = [];
        for ($i = 0; count($seeds) < 101; $i++) {
            $seed = "seed-{$i}";
            $roll = (new ProvablyFair($game, $seed))->result()->result()[0];
            $seeds[(int) $roll] ??= $seed;
        }

        return $seeds;
    }

    private function bet(string $target, int $value): Data
    {
        return new Data(null, [
            'api_id' => 'dice', 'bet' => 10, 'currency' => 'credits', 'demo' => false, 'quick' => true,
            'data' => ['target' => $target, 'value' => $value],
        ]);
    }
}
