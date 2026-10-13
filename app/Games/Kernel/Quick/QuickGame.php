<?php namespace App\Games\Kernel\Quick;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\ModuleSeeder;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use App\Models\Transaction;

abstract class QuickGame extends Game {

    private string $server_seed;

    abstract function start($user, Data $data);

    abstract function isLoss(ProvablyFairResult $result, Data $data): bool;

    public function process(Data $data) {
        $this->server_seed = (new ModuleSeeder($this, $data->demo(), $data, null))->find(function(ProvablyFairResult $result) use($data) {
            return $this->isLoss($result, $data);
        });

        $result = $this->start($data->guest() ? null : $data->user(), $data);
        $result_data = $result->toArray($data);
        if(!isset($result_data['code']) && !$data->guest()) {
            $balance = $data->user()->balance(Currency::find($data->currency()))->demo($data->demo())->quiet();
            if(!$balance->subtract($data->bet(), Transaction::builder()->game($this->metadata()->id())->message('Game')->get())) {
                return ['code' => -4, 'message' => 'Not enough money'];
            }
            if($result->profit() > 0 && !$balance->add($result->profit(), Transaction::builder()->game($this->metadata()->id())->message('Win')->get())) {
                $balance->add($data->bet(), Transaction::builder()->game($this->metadata()->id())->message('Bet refund')->get());
                return ['code' => -1, 'message' => 'Could not settle game result'];
            }
            \App\Services\VipService::onBet($data->user(), $data->bet(), $data->demo());

        } else return $result_data;

        if(!$data->guest()) {
            $game = \App\Models\Game::create([
                'user' => $data->user()->_id,
                'game' => $this->metadata()->id(),
                'wager' => $data->bet(),
                'multiplier' => $result->multiplier(),
                'status' => $result->profit() > 0 ? ($result->multiplier() < 1 ? 'lose' : 'win') : 'lose',
                'profit' => $result->profit(),
                'server_seed' => $result->seed(),
                'client_seed' => $this->client_seed(),
                'nonce' => $result->nonce(),
                'data' => $result->database_data(),
                'type' => 'quick',
                'demo' => $data->demo(),
                'currency' => $data->currency()
            ]);

            app(\App\Services\SupabaseService::class)->recordGame($game, $data->user());
            \App\Services\BountyService::check($data->user(), $game);
            event(new \App\Events\LiveBet($game, $data->user()));
        }
        return $result_data;
    }

    public function server_seed() {
        return $this->server_seed;
    }

}
