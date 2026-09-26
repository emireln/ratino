<?php namespace App\Games;

use App\Currency\Currency;
use App\Models\Game;
use App\Games\Kernel\Data;
use App\Games\Kernel\GameCategory;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\Module\General\Wrapper\MultiplierCanBeLimited;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use App\Games\Kernel\Quick\QuickGame;
use App\Games\Kernel\Quick\SuccessfulQuickGameResult;
use App\Games\Kernel\RejectedGameResult;

class Roulette extends QuickGame implements MultiplierCanBeLimited {

    private array $rows = [
        "first" => ['3', '6', '9', '12', '15', '18', '21', '24', '27', '30', '33', '36'],
        "second" => ['2', '5', '8', '11', '14', '17', '20', '23', '26', '29', '32', '35'],
        "third" => ['1', '4', '7', '10', '13', '16', '19', '22', '25', '28', '31', '34'],

        "red" => ['3', '9', '12', '18', '21', '27', '30', '36', '5', '14', '23', '32', '1', '7', '16', '19', '25', '34'],
        "black" => ['6', '15', '24', '33', '2', '8', '11', '17', '20', '26', '29', '35', '4', '10', '13', '22', '28', '31'],

        "numeric" => [
            "first" => ['3', '6', '9', '12', '2', '5', '8', '11', '1', '4', '7', '10'],
            "second" => ['15', '18', '21', '24', '14', '17', '20', '23', '13', '16', '19', '22'],
            "third" => ['27', '30', '33', '36', '26', '29', '32', '35', '25', '28', '31', '34']
        ],

        "half" => [
            "first" => ['3', '6', '9', '12', '15', '18', '2', '5', '8', '11', '14', '17', '1', '4', '7', '10', '13', '16'],
            "second" => ['21', '24', '27', '30', '33', '36', '20', '23', '26', '29', '32', '35', '19', '22', '25', '28', '31', '34']
        ],

        "e/o" => [
            "even" => ['6', '12', '18', '24', '30', '36', '2', '8', '14', '20', '26', '32', '4', '10', '16', '22', '28', '34'],
            "odd" => ['3', '9', '15', '21', '27', '33', '5', '11', '17', '23', '29', '35', '1', '7', '13', '19', '25', '31']
        ]
    ];

    function metadata(): Metadata {
        return new class extends Metadata {
            function id(): string {
                return 'roulette';
            }

            function name(): string {
                return 'Roulette';
            }

            function icon(): string {
                return 'fas fa-circle-notch';
            }

            public function category(): array {
                return [GameCategory::$originals];
            }
        };
    }

    function start($user, Data $data) {
        $totalBet = 0;
        foreach((array) $data->game()->bet as $key => $value) $totalBet += $value;
        if($totalBet < Currency::find($data->currency())->minBet() || ($user != null && $user->balance(Currency::find($data->currency()))->demo($data->demo())->get() < $totalBet)) return new RejectedGameResult(-1, 'Invalid wager value (chips)');
        $data->bet($totalBet);

        return new SuccessfulQuickGameResult((new ProvablyFair($this))->result(), function(SuccessfulQuickGameResult $result, array $transformedResults) use($user, $data, $totalBet) {
            $multiplier = $this->getMultiplier($transformedResults, $data);
            if($multiplier > 0) {
                $profit = round($data->bet() * $multiplier, 2);
                $result->winCustom($user, $data, $profit, $multiplier);
            } else $result->lose();

            $result->delay($data->quick() ? 1500 : 5000);

            return [
                'slot' => $transformedResults[0]
            ];
        });
    }

    public function customWagerCalculation(Data $data): ?bool {
        $bets = (array) ($data->game()->bet ?? []);
        $validKeys = array_merge(range(0, 36), ['row1', 'row2', 'row3', '1-12', '13-24', '25-36', 'red', 'black', 'even', 'odd', '1-18', '19-36']);
        $validKeys = array_map('strval', $validKeys);
        $total = 0.0;

        foreach($bets as $key => $value) {
            if(!in_array((string) $key, $validKeys, true) || !is_numeric($value)) return false;
            $amount = (float) $value;
            if(!is_finite($amount) || $amount < 0 || abs(round($amount, 2) - $amount) > 0.000001) return false;
            $total += $amount;
        }

        $total = round($total, 2);
        if($total < Currency::find($data->currency())->minBet()) return false;
        $data->bet($total);
        return true;
    }

    private function getMultiplier(array $result, Data $data) {
        $number = $result[0];
        $totalBet = 0.0;
        $payout = 0.0;

        foreach((array) $data->game()->bet as $key => $value) {
            $value = (float) $value;
            $totalBet += $value;
            if($value == 0) continue;
            if(is_numeric($key) && intval($key) >= 0 && intval($key) <= 36 && $number == intval($key)) $payout += $value * HouseEdgeModule::apply($this, 36);
            else if($key === "row1" && in_array(strval($number), $this->rows['first'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === "row2" && in_array(strval($number), $this->rows['second'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === "row3" && in_array(strval($number), $this->rows['third'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === "1-12" && in_array(strval($number), $this->rows['numeric']['first'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === "13-24" && in_array(strval($number), $this->rows['numeric']['second'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === "25-36" && in_array(strval($number), $this->rows['numeric']['third'])) $payout += $value * HouseEdgeModule::apply($this, 3);
            else if($key === 'red' && in_array(strval($number), $this->rows['red'])) $payout += $value * HouseEdgeModule::apply($this, 2);
            else if($key === 'black' && in_array(strval($number), $this->rows['black'])) $payout += $value * HouseEdgeModule::apply($this, 2);
            else if($key === 'even' && in_array(strval($number), $this->rows['e/o']['even'])) $payout += $value * HouseEdgeModule::apply($this, 2);
            else if($key === 'odd' && in_array(strval($number), $this->rows['e/o']['odd'])) $payout += $value * HouseEdgeModule::apply($this, 2);
            else if($key === '1-18' && in_array(strval($number), $this->rows['half']['first'])) $payout += $value * HouseEdgeModule::apply($this, 2);
            else if($key === "19-36" && in_array(strval($number), $this->rows['half']['second'])) $payout += $value * HouseEdgeModule::apply($this, 2);
        }

        return $totalBet > 0 ? $payout / $totalBet : 0;
    }

    public function usesCustomWagerCalculations() {
        return true;
    }

    public function isLoss(ProvablyFairResult $result, Data $data): bool {
        return $this->getMultiplier($result->result(), $data) <= 1;
    }

    function result(ProvablyFairResult $result): array {
        return [floor($result->extractFloat() * 37)];
    }

    public function multiplier(?Game $game, ?Data $data, ProvablyFairResult $result): float {
        return $this->getMultiplier($result->result(), $data);
    }

}
