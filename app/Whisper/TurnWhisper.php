<?php namespace App\Whisper;

use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Game;
use App\Games\Kernel\Module\ModuleSeeder;
use App\Games\Kernel\Multiplayer\MultiplayerGame;
use App\Games\Kernel\ProvablyFairResult;

class TurnWhisper extends Whisper {

    public function event(): string {
        return "Turn";
    }

    public function process($data): array {
        if(!isset($data->id) || !is_string($data->id) || strlen($data->id) !== 24 || !ctype_xdigit($data->id)) return ['code' => 1, 'message' => 'Invalid game id'];
        $game = \App\Models\Game::where('_id', $data->id)->first();
        if($game == null) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->user == null || $this->user == null || strval($game->user) !== strval($this->user->_id)) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->status !== 'in-progress' || ($game->settled ?? false)) return ['code' => 2, 'message' => 'Game is finished'];

        $api_game = Game::find($game->game);
        if(!($api_game instanceof ExtendedGame)) return ['code' => 3, 'message' => 'Unsupported game operation'];
        if($api_game instanceof MultiplayerGame) return ['code' => 3, 'message' => 'Unsupported game operation'];
        $turnData = (array) $data->data;
        if(!$api_game->isValidTurnData($game, $turnData)) return ['code' => 4, 'message' => 'Invalid game turn'];

        $currentTurn = (int) $game->data['turn'];
        $server_seed = $game->server_seed;
        if($api_game->shouldReseedTurn($game)) {
            $server_seed = (new ModuleSeeder($api_game, $game->demo, null, $game))->find(function(ProvablyFairResult $result) use($api_game, $game, $turnData) {
                return $api_game->isLoss($result, $game, $turnData);
            }, $game->server_seed);
        }

        $reserved = \App\Models\Game::where('_id', $game->_id)
            ->where('status', 'in-progress')
            ->where('settled', '!=', true)
            ->where('turn_lock', '!=', true)
            ->where('data.turn', $currentTurn)
            ->update([
            'turn_lock' => true,
            'server_seed' => $server_seed,
            'data' => [
                'turn' => $currentTurn + 1,
                'history' => $game->data['history'],
                'user_data' => $game->data['user_data'],
                'game_data' => $game->data['game_data']
            ]
        ]);
        if(!$reserved) return ['code' => 2, 'message' => 'Game is finished'];
        $game->refresh();

        try {
            $turnResult = $api_game->turn($game, $turnData)->toArray();
        } catch (\Throwable $exception) {
            \App\Models\Game::where('_id', $game->_id)->where('turn_lock', true)->update(['turn_lock' => false]);
            throw $exception;
        }

        switch($turnResult['type']) {
            case 'fail':
                $api_game->setTurn($game, $api_game->getTurn($game) - 1);
                break;
            case 'lose':
                $game->update(['status' => 'lose']);
                break;
            case 'finish':
                $game->update(['status' => 'win']);
                break;
        }

        \App\Models\Game::where('_id', $game->_id)->where('turn_lock', true)->update(['turn_lock' => false]);
        $game->refresh();
        if(in_array($turnResult['type'], ['lose', 'finish'], true)) $api_game->finish($game);

        return array_merge($turnResult, [
            'game' => $game->makeHidden('server_seed')->makeHidden('nonce')->makeHidden('data')->toArray(),
            'turn' => $api_game->getTurn($game)
        ]);
    }

}
