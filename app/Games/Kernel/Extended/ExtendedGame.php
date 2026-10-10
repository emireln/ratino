<?php namespace App\Games\Kernel\Extended;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;
use App\Games\Kernel\Multiplayer\MultiplayerGame;
use App\Games\Kernel\ProvablyFairResult;
use App\Models\Transaction;
use App\Models\User;

abstract class ExtendedGame extends Game {

    public abstract function start(\App\Models\Game $game);

    public abstract function turn(\App\Models\Game $game, array $turnData): Turn;

    abstract function isLoss(ProvablyFairResult $result, \App\Models\Game $game, array $turnData): bool;

    public function process(Data $data) {
        if($data->guest()) return ['code' => -9, 'message' => 'You need to login in order to play this game'];
        if(!$this->acceptsDemo() && $data->demo()) return ['code' => -7, 'message' => 'This game does not accept any demo bets'];
        if($this instanceof MultiplayerGame && $this->state()->hasBetFrom($data->user()->_id)) return ['code' => -6, 'message' => "Can't place more than one bet"];
        if(!$this->acceptBet($data)) return ['code' => -6, 'message' => "Game is not accepting bets at this stage"];

        $balance = $data->user()->balance(Currency::find($data->currency()))->demo($data->demo());
        if(!$balance->subtract($data->bet(), Transaction::builder()->game($data->id())->message('Game')->get())) return ['code' => -4, 'message' => 'Not enough money'];
        \App\Services\VipService::onBet($data->user(), $data->bet(), $data->demo());

        $game = \App\Models\Game::create([
            'id' => \App\Models\Game::count() + 1,
            'user' => $data->guest() ? null : $data->user()->_id,
            'game' => $this->metadata()->id(),
            'wager' => $data->bet(),
            'status' => 'in-progress',
            'profit' => 0,
            'server_seed' => $this->server_seed(),
            'client_seed' => $this->client_seed(),
            'nonce' => $this->nonce(),
            'multiplier' => 0,
            'currency' => $data->currency(),
            'data' => [
                'turn' => 0,
                'history' => [],
                'game_data' => [],
                'user_data' => $data->toArray()
            ],
            'demo' => $data->demo(),
            'type' => $this instanceof MultiplayerGame ? 'multiplayer' : 'extended',
            'settled' => false,
            'turn_lock' => false
        ]);

        $this->start($game);

        return [
            'response' => [
                'id' => $game->_id,
                'wager' => $data->bet(),
                'type' => $this instanceof MultiplayerGame ? 'multiplayer' : 'extended',
                'canBeFinished' => $this instanceof MultiplayerGame ? $this->canBeFinished() : null
            ]
        ];
    }

    public function finish(\App\Models\Game $game) {
        $turn = $this->getTurn($game);
        $claimQuery = \App\Models\Game::where('_id', $game->_id)
            ->where('settled', '!=', true)
            ->where('turn_lock', '!=', true)
            ->whereIn('status', ['in-progress', 'win', 'lose']);
        if($turn === 0) $claimQuery->where('status', 'in-progress');
        $claim = $claimQuery->update(['settled' => true]);
        if(!$claim) return false;

        if($turn == 0) {
            $cancellation = $this->handleCancellation($game);
            if(is_array($cancellation) && isset($cancellation['error'])) {
                \App\Models\Game::where('_id', $game->_id)->update(['settled' => false]);
                return false;
            }
            return true;
        }

        $status = $game->status === 'lose' || $game->multiplier <= 0 ? 'lose' : 'win';
        $profit = $status === 'lose' ? 0 : round($game->wager * $game->multiplier, 2);
        $game->update(['profit' => $profit, 'status' => $status]);

        if($game->user != null) {
            $currency = Currency::find($game->currency);
            $user = User::where('_id', $game->user)->first();
            if($user == null) return true;

            if($profit > 0) $user->balance($currency)->demo($game->demo)->quiet()->add($profit, Transaction::builder()->game($game->game)->message('Win')->get());
            app(\App\Services\SupabaseService::class)->recordGame($game, $user);
            \App\Services\BountyService::check($user, $game);
            event(new \App\Events\LiveBet($game, $user));
        }
        return true;
    }

    protected function handleCancellation(\App\Models\Game $game) {
        if($this instanceof MultiplayerGame && !$this->allowCancellation()) return;

        $game->update([
            'status' => 'cancelled'
        ]);

        $user = User::where('_id', $game->user)->first();
        if($user != null) $user->balance(Currency::find($game->currency))->demo($game->demo)->add($game->wager, Transaction::builder()->game($game->game)->message('Cancellation')->get());
    }

    protected function acceptBet(Data $data) {
        return true;
    }

    protected function acceptsDemo() {
        return true;
    }

    public function isValidTurnData(\App\Models\Game $game, array $turnData): bool {
        return true;
    }

    public function shouldReseedTurn(\App\Models\Game $game): bool {
        return $this->getTurn($game) === 0;
    }

    public function inHistory(\App\Models\Game $game, $validate) {
        return in_array($validate, $game->data['history']);
    }

    public function userData(\App\Models\Game $game) {
        return $game->data['user_data'];
    }

    public function gameData(\App\Models\Game $game) {
        return $game->data['game_data'];
    }

    public function pushData(\App\Models\Game $game, array $value) {
        $data = $game->data['game_data'];
        $data = array_merge($data, $value);
        $game->update([
            'data' => [
                'turn' => $game->data['turn'],
                'history' => $game->data['history'],
                'game_data' => $data,
                'user_data' => $game->data['user_data']
            ]
        ]);
    }

    public function pushHistory(\App\Models\Game $game, $validate) {
        $history = $game->data['history'];
        array_push($history, $validate);
        $game->update([
            'data' => [
                'turn' => $game->data['turn'],
                'history' => $history,
                'game_data' => $game->data['game_data'],
                'user_data' => $game->data['user_data']
            ]
        ]);
    }

    public function setTurn(\App\Models\Game $game, int $turn) {
        $game->update([
            'data' => [
                'turn' => $turn,
                'history' => $game->data['history'],
                'game_data' => $game->data['game_data'],
                'user_data' => $game->data['user_data']
            ]
        ]);
    }

    public function getTurn(\App\Models\Game $game): int {
        return $game->data['turn'];
    }

    public function getBotTurnData($turnId): array {
        return [];
    }

}
