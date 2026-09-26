<?php namespace App\Games;

use App\Models\Game;
use App\Games\Kernel\Data;
use App\Games\Kernel\Extended\ContinueGame;
use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Extended\FailedTurn;
use App\Games\Kernel\Extended\FinishGame;
use App\Games\Kernel\Extended\LoseGame;
use App\Games\Kernel\Extended\Turn;
use App\Games\Kernel\GameCategory;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\Module\General\Wrapper\MultiplierCanBeLimited;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class Mines extends ExtendedGame implements MultiplierCanBeLimited {

    function metadata(): Metadata {
        return new class extends Metadata {
            function id(): string {
                return 'mines';
            }

            function name(): string {
                return 'Mines';
            }

            function icon(): string {
                return 'fas fa-bomb';
            }

            public function category(): array {
                return [GameCategory::$originals];
            }
        };
    }

    public function process(Data $data) {
        $mines = filter_var($data->game()->mines ?? null, FILTER_VALIDATE_INT);
        if($mines === false || $mines < 2 || $mines > 24) return ['code' => -1, 'message' => 'Mines must be between 2 and 24'];
        return parent::process($data);
    }

    public function start(\App\Models\Game $game) {}

    public function isValidTurnData(Game $game, array $turnData): bool {
        if(!array_key_exists('id', $turnData)) return false;
        $ids = is_array($turnData['id']) ? $turnData['id'] : [$turnData['id']];
        $mines = intval($this->userData($game)['data']['mines'] ?? 0);
        $history = array_map(fn($id) => $this->tileId($id), $game->data['history'] ?? []);
        if(in_array(null, $history, true)) return false;
        if($mines < 2 || $mines > 24 || count($ids) === 0 || count($ids) > 25 - $mines - count($history)) return false;

        $seen = [];
        foreach($ids as $id) {
            $id = $this->tileId($id);
            if($id === null || in_array($id, $history, true) || in_array($id, $seen, true)) return false;
            $seen[] = $id;
        }
        return true;
    }

    public function getModuleData(\App\Models\Game $game) {
        return floatval($this->userData($game)['data']['mines']);
    }

    public function turn(\App\Models\Game $game, array $turnData): Turn {
        if(!$this->isValidTurnData($game, $turnData)) return new FailedTurn($game, []);
        if(is_array($turnData['id'])) {
            foreach ($turnData['id'] as $id) {
                $state = $this->turn($game, ['id' => $id]);
                if(!($state instanceof ContinueGame)) return $state;
                $this->setTurn($game, $this->getTurn($game) + 1);
            }
            return new ContinueGame($game, []);
        }

        $id = $this->tileId($turnData['id']);
        $this->pushHistory($game, $id);

        $grid = $this->getGrid($game, $game->server_seed);
        if($grid[$id] == 1) {
            $this->pushData($game, ['grid' => $grid]);
            return new LoseGame($game, ['grid' => $grid]);
        }

        $game->update([
            'multiplier' => $this->data()[$this->userData($game)['data']['mines']][$this->getTurn($game)]
        ]);

        $this->pushData($game, [strval($this->getTurn($game)) => $id]);

        if($this->getTurn($game) - 1 == (5 * 5) - 1 - intval($this->userData($game)['data']['mines'])) {
            $this->pushData($game, ['grid' => $grid]);
            return new FinishGame($game, $grid);
        }
        return new ContinueGame($game, []);
    }

    public function data(): array {
        $cacheKey = 'win5x:minesMultipliers:v2:'.HouseEdgeModule::get($this, 0.99);
        if(!Cache::has($cacheKey)) {
            $output = [];

            $factorial = function ($n) use (&$factorial) {
                return $n == 0 ? 1 : $n * $factorial($n - 1);
            };

            $nCr = function ($n, $r) use ($factorial) {
                return $factorial($n) / $factorial($r) / $factorial($n - $r);
            };

            $calculateMultiplier = function ($mines, $diamonds) use ($nCr) {
                return HouseEdgeModule::get($this, 0.99) * $nCr(25, $diamonds) / $nCr(25 - $mines, $diamonds);
            };

            for ($mines = 2; $mines <= 24; $mines++) {
                $grid = [];
                for ($i = 1; $i <= 25 - $mines; $i++)
                    $grid[$i] = $calculateMultiplier($mines, $i);
                $output[$mines] = $grid;
            }

            Cache::forever($cacheKey, json_encode($output));
        }
        return json_decode(Cache::get($cacheKey), true);
    }

    private function getGrid(Game $game, string $server_seed): array {
        $bombs = intval($this->userData($game)['data']['mines']);
        if($bombs < 2 || $bombs > 24) throw new \UnexpectedValueException('Invalid Mines configuration');
        $bombs_grid = (new ProvablyFair($this, $server_seed))->result()->result()[$bombs - 2];
        $grid = [];

        for($i = 0; $i < 5 * 5; $i++) array_push($grid, 0);
        foreach($bombs_grid as $value) $grid[$value] = 1;

        return $grid;
    }

    public function isLoss(ProvablyFairResult $result, \App\Models\Game $game, array $turnData): bool {
        if(!$this->isValidTurnData($game, $turnData)) return true;
        $grid = $this->getGrid($game, $result->server_seed());
        foreach($game->data['history'] ?? [] as $id) {
            $id = $this->tileId($id);
            if($id === null || $grid[$id] !== 0) return false;
        }
        foreach(is_array($turnData['id']) ? $turnData['id'] : [$turnData['id']] as $id) {
            if($grid[$this->tileId($id)] === 1) return true;
        }
        return false;
    }

    private function tileId($id): ?int {
        if(is_int($id)) return $id >= 0 && $id < 25 ? $id : null;
        if(!is_string($id) || !preg_match('/^(0|[1-9][0-9]*)$/D', $id)) return null;
        $id = intval($id);
        return $id < 25 ? $id : null;
    }

    function result(ProvablyFairResult $result): array {
        $max_squares = 24;

        $output = [];
        for($bombs = 2; $bombs <= 24; $bombs++) {
            $squares = range(0, $max_squares);

            $tiles = [];
            for ($i = 0; $i < $bombs; $i++) array_push($tiles, array_splice($squares, floor($result->extractFloats($bombs)[$i] * ($max_squares - $i + 1)), 1)[0]);
            array_push($output, $tiles);
        }

        return $output;
    }

    public function multiplier(?Game $game, ?Data $data, ProvablyFairResult $result): float {
        return $this->data()[$this->userData($game)['data']['mines']][$this->getTurn($game) + 1];
    }

    public function getBotData(): array {
        return [
            'mines' => mt_rand(2, 24)
        ];
    }

    public function getBotTurnData($turnId): array {
        return [
            'id' => mt_rand(0, 5 * 5 - 1)
        ];
    }

}
