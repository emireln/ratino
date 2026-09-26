<?php

namespace App\Games\Kernel\Multiplayer;

use App\Currency\Currency;
use App\Events\MultiplayerGameBet;
use App\Games\Kernel\Data;
use App\Games\Kernel\Extended\ContinueGame;
use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Extended\Turn;
use App\Games\Kernel\ProvablyFairResult;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BountyService;
use App\Services\SupabaseService;

abstract class MultiplayerGame extends ExtendedGame
{
    abstract public function nextGame();

    abstract public function onDispatchedFinish();

    abstract public function startChain();

    protected function getPlayerData(Game $game): array
    {
        return [];
    }

    public function state(): MultiplayerGameStateBuilder
    {
        return new MultiplayerGameStateBuilder($this);
    }

    public function getActiveGames()
    {
        return Game::where('game', $this->metadata()->id())->where('status', 'in-progress')
            ->where('server_seed', $this->state()->serverSeed())
            ->where('client_seed', $this->state()->clientSeed())->get();
    }

    public function start(Game $game)
    {
        $data = $this->getPlayerData($game);
        $user = User::where('_id', $game->user)->first();
        if ($user == null) {
            return;
        }
        $this->state()->players([
            'user' => $user->toArray(),
            'game' => $game->toArray(),
            'data' => $this->getPlayerData($game),
        ]);
        event(new MultiplayerGameBet($user, $game, $data));
    }

    public function turn(Game $game, array $turnData): Turn
    {
        return new ContinueGame($game, []);
    }

    protected function win(Game $game, float $multiplier, int $delay)
    {
        $profit = round($game->wager * $multiplier, 2);
        $settled = Game::where('_id', $game->_id)
            ->where('status', 'in-progress')
            ->where('settled', '!=', true)
            ->where('turn_lock', '!=', true)
            ->update([
            'settled' => true,
            'status' => $multiplier <= 0 ? 'lose' : 'win',
            'profit' => $profit,
            'multiplier' => $multiplier,
        ]);
        if(!$settled) return;
        $game->refresh();

        $user = User::where('_id', $game->user)->first();
        if($user == null) return;
        if($profit > 0) {
            $currency = Currency::find($game->currency);
            $user->balance($currency)->demo($game->demo)->quiet()
                ->add($profit, Transaction::builder()->message($this->metadata()->name())->game($this->metadata()->id())->get());
        }
        app(SupabaseService::class)->recordGame($game, $user);
        BountyService::check($user, $game);
    }

    protected function acceptBet(Data $data)
    {
        return $this->state()->betting();
    }

    protected function canBeFinished(): bool
    {
        return false;
    }

    public function canPlayerFinish(): bool
    {
        return $this->canBeFinished();
    }

    protected function allowCancellation(): bool
    {
        return false;
    }

    protected function acceptsDemo()
    {
        return true;
    }

    public function ignoresMultipleClientTabs()
    {
        return true;
    }

    public function isLoss(ProvablyFairResult $result, Game $game, array $turnData): bool
    {
        return true;
    }

    public function client_seed()
    {
        return $this->state()->clientSeed();
    }

    public function server_seed()
    {
        return $this->state()->serverSeed();
    }

    public function nonce()
    {
        return $this->state()->nonce();
    }

    public function data(): array
    {
        return $this->state()->toArray();
    }
}
