<?php namespace App\Whisper;

use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Game;
use App\Games\Kernel\Multiplayer\MultiplayerGame;

class FinishWhisper extends Whisper {

    public function event(): string {
        return 'Finish';
    }

    public function process($data): array {
        if(!isset($data->id) || !is_string($data->id) || strlen($data->id) !== 24 || !ctype_xdigit($data->id)) return ['code' => 1, 'message' => 'Invalid game id'];
        $game = \App\Models\Game::where('_id', $data->id)->first();
        if($game == null) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->user == null || $this->user == null || strval($game->user) !== strval($this->user->_id)) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->status !== 'in-progress' || ($game->settled ?? false)) return ['code' => 2, 'message' => 'Game is finished'];

        $api_game = Game::find($game->game);
        if(!($api_game instanceof ExtendedGame)) return ['code' => 3, 'message' => 'Unsupported game operation'];
        if($api_game instanceof MultiplayerGame && !$api_game->canPlayerFinish()) return ['code' => 4, 'message' => 'This game cannot be finished manually'];

        if(!$api_game->finish($game)) return ['code' => 2, 'message' => 'Game is finished'];
        $game->refresh();
        // Multiplayer round seed stays secret until the round ends (MultiplayerGameFinished).
        if($api_game instanceof MultiplayerGame) $game->makeHidden('server_seed');
        return [
            'game' => $game->toArray()
        ];
    }

}
