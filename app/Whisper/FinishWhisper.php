<?php namespace App\Whisper;

use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Game;

class FinishWhisper extends Whisper {

    public function event(): string {
        return 'Finish';
    }

    public function process($data): array {
        if(!isset($data->id) || !is_string($data->id) || strlen($data->id) !== 24 || !ctype_xdigit($data->id)) return ['code' => 1, 'message' => 'Invalid game id'];
        $game = \App\Models\Game::where('_id', $data->id)->first();
        if($game == null) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->user != null && ($this->user == null || strval($game->user) !== strval($this->user->_id))) return ['code' => 1, 'message' => 'Invalid game id'];
        if($game->status !== 'in-progress') return ['code' => 2, 'message' => 'Game is finished'];

        $api_game = Game::find($game->game);
        if(!($api_game instanceof ExtendedGame)) return ['code' => 3, 'message' => 'Unsupported game operation'];

        $api_game->finish($game);
        return [
            'game' => $game->toArray()
        ];
    }

}
