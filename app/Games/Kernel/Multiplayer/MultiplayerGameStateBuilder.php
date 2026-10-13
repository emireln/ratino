<?php

namespace App\Games\Kernel\Multiplayer;

use App\Events\MultiplayerBettingStateChange;
use App\Events\MultiplayerDataUpdate;
use App\Games\Kernel\Game;
use App\Games\Kernel\ProvablyFair;
use App\Models\MultiplayerGameState;
use Illuminate\Support\Facades\DB;
use MongoDB\BSON\ObjectId;

class MultiplayerGameStateBuilder
{
    private Game $game;

    private MultiplayerGameState $state;

    public function __construct(Game $game)
    {
        $state = MultiplayerGameState::where('game', $game->metadata()->id())->first();
        if ($state == null) {
            $state = MultiplayerGameState::create([
                'game' => $game->metadata()->id(),
                'history' => [],
                'players' => [],
                'data' => [],
                'timestamp' => 0,
                'can_bet' => false,
                'server_seed' => ProvablyFair::generateServerSeed(),
                'client_seed' => ProvablyFair::generateServerSeed(),
                'nonce' => mt_rand(1, 10000000),
            ]);
        }

        $this->game = $game;
        $this->state = $state;
    }

    public function clientSeed($set = null): string
    {
        if ($set != null) {
            $this->state->update(['client_seed' => $set]);
        }

        return $this->state->client_seed;
    }

    public function serverSeed($set = null): string
    {
        if ($set != null) {
            $this->state->update(['server_seed' => $set]);
        }

        return $this->state->server_seed;
    }

    public function nonce($set = null): int
    {
        if ($set != null) {
            $this->state->update(['nonce' => $set]);
        }

        return $this->state->nonce;
    }

    public function history($add = null): array
    {
        if ($add != null) {
            $history = $this->state->history;
            array_unshift($history, $add);
            if (count($history) >= 15) {
                array_pop($history);
            }

            $this->state->update(['history' => $history]);
        }

        return $this->state->history;
    }

    public function hasBetFrom(string $userId)
    {
        foreach ($this->players() as $player) {
            if ($player['user']['_id'] === $userId) {
                return true;
            }
        }

        return false;
    }

    /**
     * Appends a bet unless this user already has one this round. One conditional $push, so two
     * concurrent bets cannot both land, and neither can overwrite the other's entry.
     */
    public function addPlayer(array $player): bool
    {
        return $this->collection()->updateOne(
            ['_id' => $this->objectId(), 'players.user._id' => ['$ne' => $player['user']['_id']]],
            ['$push' => ['players' => $player]]
        )->getMatchedCount() === 1;
    }

    /** Read raw: Eloquent hydration renames a nested '_id' to 'id', which would break user matching. */
    public function players(): array
    {
        $doc = $this->collection()->findOne(['_id' => $this->objectId()], [
            'projection' => ['players' => 1],
            'typeMap' => ['root' => 'array', 'document' => 'array', 'array' => 'array'],
        ]);
        $players = $doc['players'] ?? [];

        // Rounds written before the cast removal stored players as a JSON string.
        return is_array($players) ? $players : (json_decode((string) $players, true) ?: []);
    }

    private function collection()
    {
        return DB::connection('mongodb')->getCollection('multiplayer_game_states');
    }

    private function objectId(): ObjectId
    {
        return new ObjectId((string) $this->state->_id);
    }

    /**
     * Updates data array and erases previous data.
     *
     * @return array
     */
    public function data(?array $set = null)
    {
        if ($set != null) {
            $this->state->update(['data' => $set]);
        }

        return $this->state->data;
    }

    /**
     * Updates data array without erasing previous data.
     *
     * @return MultiplayerGameStateBuilder
     */
    public function pushData(array $data)
    {
        $this->state->update(['data' => array_merge($this->state->data ?? [], $data)]);

        return $this;
    }

    public function sendDataUpdateEvent()
    {
        event(new MultiplayerDataUpdate($this->game, $this->state->data ?? []));

        return $this;
    }

    public function resetPlayers()
    {
        $this->state->update(['players' => []]);
    }

    public function betting($enable = null): bool
    {
        if ($enable !== null) {
            $this->state->update(['can_bet' => $enable]);
            event(new MultiplayerBettingStateChange($this->game, $enable));
        }

        return $this->state->can_bet;
    }

    public function timestamp($set = null): float
    {
        if ($set != null) {
            $this->state->update(['timestamp' => $set]);
        }

        return $this->state->timestamp;
    }

    public function toArray(): array
    {
        // players come from the raw read so user._id survives (see players()).
        return array_merge($this->state->makeHidden('server_seed')->toArray(), ['players' => $this->players()]);
    }
}
