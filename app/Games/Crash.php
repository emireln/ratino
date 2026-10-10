<?php namespace App\Games;

use App\Currency\Currency;
use App\Events\MultiplayerTimerStart;
use App\Games\Kernel\Data;
use App\Games\Kernel\GameCategory;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\Multiplayer\MultiplayerGame;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use App\Jobs\Multiplayer\MultiplayerDisableBetAccepting;
use App\Jobs\Multiplayer\MultiplayerFinishAndSetupNextGame;
use App\Models\Transaction;
use App\Models\User;

class Crash extends MultiplayerGame {

    function metadata(): Metadata {
        return new class extends Metadata {
            function id(): string {
                return 'crash';
            }

            function name(): string {
                return 'Crash';
            }

            function icon(): string {
                return 'fas fa-chart-line';
            }

            public function category(): array {
                return [GameCategory::$originals];
            }
        };
    }

    public function startChain() {
        dispatch(new MultiplayerFinishAndSetupNextGame($this, ['multiplier' => 1], now()->addSeconds(6)));
    }

    public function nextGame() {
        $this->state()->resetPlayers();
        $this->state()->clientSeed(ProvablyFair::generateServerSeed());
        $this->state()->serverSeed(ProvablyFair::generateServerSeed());
        $this->state()->nonce(now()->timestamp);

        $multiplier = (new ProvablyFair($this))->result()->result()[0];
        if($multiplier < 1) $multiplier = 1.1;

        $timeInMilliseconds = 0;
        $simulation = 1; $suS =  0;

        while($simulation < $multiplier) {
            $simulation += 0.05 / 15 + $suS;
            $timeInMilliseconds += 2000 / 15 / 3;
            if($simulation >= 5.5) {
                $suS += 0.05 / 15;
                $timeInMilliseconds += 4000 / 15 / 3;
            }
        }

        $this->state()->betting(true);
        $this->state()->timestamp(microtime(true) + 6);

        dispatch((new MultiplayerDisableBetAccepting($this))->delay(now()->addSeconds(6)));

        event(new MultiplayerTimerStart($this));

        dispatch((new MultiplayerFinishAndSetupNextGame($this, ['multiplier' => $multiplier], now()->addSeconds(6)))
            ->delay(now()->addMilliseconds($timeInMilliseconds + 6000)));
    }

    public function onDispatchedFinish() {
        $crashAt = (new ProvablyFair($this, $this->server_seed()))->result()->result()[0];
        $this->state()->history([
            'server_seed' => $this->server_seed(),
            'client_seed' => $this->client_seed(),
            'nonce' => $this->nonce(),
            'multiplier' => $crashAt
        ]);

        foreach($this->getActiveGames() as $game) {
            $settled = \App\Models\Game::where('_id', $game->_id)
                ->where('status', 'in-progress')
                ->where('settled', '!=', true)
                ->update(['status' => 'lose', 'settled' => true, 'profit' => 0, 'multiplier' => 0]);
            if(!$settled) continue;

            $game->refresh();
            $user = User::where('_id', $game->user)->first();
            if($user == null) continue;
            app(\App\Services\SupabaseService::class)->recordGame($game, $user);
            \App\Services\BountyService::check($user, $game);
            event(new \App\Events\LiveBet($game, $user));
        }
    }

    protected function allowCancellation(): bool {
        return true;
    }

    protected function canBeFinished(): bool {
        return true;
    }

    protected function handleCancellation(\App\Models\Game $game) {
        if($game->server_seed !== $this->state()->serverSeed() || $game->status !== 'in-progress') return ['error' => [1, 'This Crash game is invalid']];

        $user = User::where('_id', $game->user)->first();
        if($user == null) return ['error' => [1, 'This Crash game has no player']];

        $multiplier = $this->getCurrentMultiplier();
        $crashAt = (new ProvablyFair($this, $game->server_seed))->result()->result()[0];
        if($multiplier >= $crashAt) {
            $game->update(['status' => 'lose', 'profit' => 0, 'multiplier' => 0]);
            app(\App\Services\SupabaseService::class)->recordGame($game, $user);
            \App\Services\BountyService::check($user, $game);
            event(new \App\Events\LiveBet($game, $user));
            return;
        }
        if($multiplier > 1000) $multiplier = 1;

        $game->update([
            'status' => 'win',
            'profit' => round($game->wager * $multiplier, 2),
            'multiplier' => $multiplier
        ]);
        $user->balance(Currency::find($game->currency))->demo($game->demo)
            ->add($game->profit, Transaction::builder()->message('Crash (Take)')->game($this->metadata()->id())->get());
        event(new \App\Events\MultiplayerBetCancellation($game, $user));
        event(new \App\Events\LiveBet($game, $user));
    }

    private function getCurrentMultiplier() {
        $start_timestamp = $this->state()->timestamp();
        if($start_timestamp < 0) return 1;

        $diffS = microtime(true) - $start_timestamp;
        if($diffS <= 0) return 1;
        $timeInMilliseconds = 0;
        $simulation = 1; $suS =  0;

        while(true) {
            $simulation += 0.05 / 15 + $suS;
            $timeInMilliseconds += 2000 / 15 / 3;
            if($simulation >= 5.5) {
                $suS += 0.05 / 15;
                $timeInMilliseconds += 4000 / 15 / 3;
            }
            if($timeInMilliseconds >= ($diffS * 1000) || $simulation > 1000) {
                return min($simulation, 1000);
            }
        }
    }

    public function isLoss(ProvablyFairResult $result, \App\Models\Game $game, array $turnData): bool {
        return $result->result()[0] <= 1.1;
    }

    function result(ProvablyFairResult $result): array {
        $max_multiplier = 1000; $house_edge = HouseEdgeModule::get($this, 0.99);
        $float_point = min($max_multiplier, $house_edge / max($result->extractFloat(), 1 / 4294967296));
        return [floor($float_point * 100) / 100];
    }

}
