<?php

namespace App\Http\Controllers\Api;

use App\Currency\Currency;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Http\Controllers\Controller;
use App\Models\Modules;
use App\Utils\APIResponse;

class DataController extends Controller {

    public function games() {
        $games = [];
        foreach (Game::list() as $game) {
            $houseEdge = new HouseEdgeModule($game, null, null, null);
            $games[] = [
                'isDisabled' => $game->isDisabled(),
                'isPlaceholder' => false,
                'name' => $game->metadata()->name(),
                'id' => $game->metadata()->id(),
                'icon' => $game->metadata()->icon(),
                'category' => $game->metadata()->category(),
                'houseEdge' => !Modules::get($game, false)->isEnabled($houseEdge)
                    ? null
                    : (float) Modules::get($game, false)->get($houseEdge, 'house_edge_option')
            ];
        }
        return APIResponse::success($games);
    }

    public function currencies() {
        return APIResponse::success(Currency::toCurrencyArray(Currency::all()));
    }

}
