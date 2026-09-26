<?php namespace App\Whisper;

use App\Currency\Currency;
use App\Games\Kernel\Data;
use App\Games\Kernel\Game;

class PlayWhisper extends Whisper {

    public function event(): string {
        return "Play";
    }

    public function process($data): array {
        if($this->user == null) return ['code' => -9, 'message' => 'You need to login in order to play this game'];

        $apiId = $data->api_id ?? null;
        if(!is_string($apiId) || $apiId === '') return ['code' => -3, 'message' => 'Unknown API game id'];
        $game = Game::find($apiId);
        if($game == null) return ['code' => -3, 'message' => 'Unknown API game id'];
        if($game->isDisabled()) return ['code' => -5, 'message' => 'Game is disabled'];

        if(!$game->ignoresMultipleClientTabs() && \App\Models\Game::where('game', $apiId)->where('user', $this->user->_id)->where('demo', false)->where('status', 'in-progress')->count() > 0) return ['code' => -8, 'message' => 'Game already has started'];

        $currencyId = $data->currency ?? 'credits';
        if(!is_string($currencyId)) return ['code' => -1, 'message' => 'Invalid currency'];
        $currency = Currency::find($currencyId);
        if($currency == null) $currency = Currency::find('credits');
        if($currency == null) return ['code' => -1, 'message' => 'Invalid currency'];

        $rawBet = $data->bet ?? null;
        if(!is_numeric($rawBet)) return ['code' => -1, 'message' => 'Invalid wager value'];
        $bet = round((float) $rawBet, 2);
        if(!is_finite($bet) || $bet <= 0) return ['code' => -1, 'message' => 'Invalid wager value'];
        $demo = filter_var(is_scalar($data->demo ?? false) ? ($data->demo ?? false) : false, FILTER_VALIDATE_BOOLEAN);
        $quick = filter_var(is_scalar($data->quick ?? false) ? ($data->quick ?? false) : false, FILTER_VALIDATE_BOOLEAN);
        if(!$game->usesCustomWagerCalculations() && $bet < $currency->minBet()) return ['code' => -1, 'message' => 'Invalid wager value'];
        if(!$game->usesCustomWagerCalculations() && $this->user->balance($currency)->demo($demo)->get() < $bet) return ['code' => -4, 'message' => 'Not enough money'];

        $gameData = $data->data ?? [];
        if(!is_array($gameData) && !is_object($gameData)) return ['code' => -1, 'message' => 'Invalid game data'];

        $data = new Data($this->user, [
            'api_id' => $apiId,
            'bet' => $bet,
            'currency' => $currency->id(),
            'demo' => $demo,
            'quick' => $quick,
            'data' => (array) $gameData
        ]);

        if(!$game->isValidPlayData($data)) return ['code' => -1, 'message' => 'Invalid game data'];
        if($game->usesCustomWagerCalculations() && $game->customWagerCalculation($data) !== true) return ['code' => -1, 'message' => 'Invalid wager value'];
        if($this->user->balance($currency)->demo($data->demo())->get() < $data->bet()) return ['code' => -4, 'message' => 'Not enough money'];

        return $game->process($data);
    }

}
