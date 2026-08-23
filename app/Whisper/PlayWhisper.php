<?php namespace App\Whisper;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;

class PlayWhisper extends Whisper {

    public function event(): string {
        return "Play";
    }

    public function process($data): array {
        $game = Game::find($data->api_id);
        if($game == null) return ['code' => -3, 'message' => 'Unknown API game id'];
        if($game->isDisabled()) return ['code' => -5, 'message' => 'Game is disabled'];

        if($this->user != null && !$game->ignoresMultipleClientTabs() && \App\Models\Game::where('game', $data->api_id)->where('user', $this->user->_id)->where('demo', false)->where('status', 'in-progress')->count() > 0) return ['code' => -8, 'message' => 'Game already has started'];

        $currency = Currency::find($data->currency ?? 'credits');
        if($currency == null) $currency = Currency::find('credits');
        if($currency == null) return ['code' => -1, 'message' => 'Invalid currency'];

        $bet = floatval($data->bet ?? 0);
        if(!$game->usesCustomWagerCalculations() && $bet < $currency->minBet()) return ['code' => -1, 'message' => 'Invalid wager value'];
        if($this->user != null && ($this->user->balance($currency)->demo($data->demo ?? false)->get() < $bet)) return ['code' => -4, 'message' => 'Not enough money'];

        $data = new Data($this->user, [
            'api_id' => $data->api_id,
            'bet' => $bet,
            'currency' => $currency->id(),
            'demo' => $data->demo ?? false,
            'quick' => $data->quick ?? false,
            'data' => (array) ($data->data ?? [])
        ]);

        return $game->process($data);
    }

}
