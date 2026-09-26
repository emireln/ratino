<?php namespace App\Games;

use App\Currency\Currency;
use App\Games\Kernel\Extended\ContinueGame;
use App\Games\Kernel\Extended\FailedTurn;
use App\Games\Kernel\Extended\ExtendedGame;
use App\Games\Kernel\Extended\FinishGame;
use App\Games\Kernel\Extended\Turn;
use App\Games\Kernel\GameCategory;
use App\Games\Kernel\Metadata;
use App\Games\Kernel\Module\General\HouseEdgeModule;
use App\Games\Kernel\ProvablyFair;
use App\Games\Kernel\ProvablyFairResult;
use App\Models\Transaction;
use App\Models\User;

class Blackjack extends ExtendedGame {

    function metadata(): Metadata {
        return new class extends Metadata {
            function id(): string {
                return 'blackjack';
            }

            function name(): string {
                return 'Blackjack';
            }

            function icon(): string {
                return 'fas fa-club';
            }

            public function category(): array {
                return [GameCategory::$originals, GameCategory::$table];
            }
        };
    }

    private function nextCard(\App\Models\Game $game, $hidden = false, ?string $server_seed = null) {
        $drawIndex = intval($this->gameData($game)['index'] ?? 0);
        if($server_seed == null) $this->pushData($game, ['index' => $drawIndex + 1]);

        $index = (new ProvablyFair($this, $server_seed == null ? $game->server_seed : $server_seed))->result()->result()[$drawIndex];
        $deck = $this->deck()[$index + 1];
        return [
            'index' => $index,
            'type' => $deck['type'],
            'value' => $deck['value'],
            'blackjack_value' => $deck['blackjackValue'],
            'hidden' => $hidden
        ];
    }

    public function start(\App\Models\Game $game) {
        $this->pushData($game, [
            'player' => [
                $this->nextCard($game),
                $this->nextCard($game)
            ],
            'dealer' => [
                $this->nextCard($game),
                $this->nextCard($game, true)
            ],
            'split' => [],
            'currentHand' => 0,
            'doubled' => [false, false],
            'doubleDrawn' => [false, false],
            'handMultipliers' => [],
            'dealerResolved' => false,
            'dealerDraw' => [],
            'insurance' => false,
            'insuranceStake' => 0
        ]);
    }

    public function isValidTurnData(\App\Models\Game $game, array $turnData): bool {
        return in_array($turnData['type'] ?? null, ['info', 'hit', 'stand', 'double', 'split', 'insurance'], true);
    }

    public function shouldReseedTurn(\App\Models\Game $game): bool {
        return false;
    }

    public function turn(\App\Models\Game $game, array $turnData): Turn {
        $user = User::where('_id', $game->user)->first();
        if($user == null) return new FailedTurn($game, ['error' => true]);

        $gameData = $this->gameData($game);
        $handIndex = intval($gameData['currentHand'] ?? 0);
        if($handIndex < 0 || $handIndex > 1) return new FailedTurn($game, ['error' => true]);
        $handKey = $handIndex === 0 ? 'player' : 'split';
        $hand = $gameData[$handKey] ?? [];
        $baseWager = floatval($this->userData($game)['bet'] ?? 0);
        $doubled = $gameData['doubled'] ?? [false, false];
        $doubleDrawn = $gameData['doubleDrawn'] ?? [false, false];
        $failed = fn() => new FailedTurn($game, ['error' => true]);

        $updateRestore = function() use(&$game) {
            $data = $game->data['user_data'];
            $data = array_merge($data, [
                'player' => $this->gameData($game)['player'],
                'dealer' => $this->gameData($game)['dealer'],
                'split'  => $this->gameData($game)['split'],
                'currentHand' => $this->gameData($game)['currentHand']
            ]);
            $game->update([
                'data' => [
                    'turn' => $game->data['turn'],
                    'history' => $game->data['history'],
                    'game_data' => $game->data['game_data'],
                    'user_data' => $data
                ]
            ]);
        };

        switch ($turnData['type'] ?? null) {
            case 'info':
                if($game->data['turn'] > 1) return $failed();
                $updateRestore();
                return new ContinueGame($game, ['player' => $this->gameData($game)['player'], 'dealer' => $this->gameData($game)['dealer'][0]]);
            case 'split':
                if(count($hand) !== 2 || count($gameData['split'] ?? []) > 0 || $hand[0]['blackjack_value'] !== $hand[1]['blackjack_value']) return $failed();
                if(!$user->balance(Currency::find($game->currency))->demo($game->demo)->subtract($baseWager, Transaction::builder()->game($this->metadata()->id())->message('Split')->get())) return $failed();
                $game->update(['wager' => $game->wager + $baseWager]);

                $this->pushData($game, [
                    'player' => [
                        $this->gameData($game)['player'][0],
                        $this->nextCard($game)
                    ],
                    'split' => [
                        $this->gameData($game)['player'][0],
                        $this->nextCard($game)
                    ],
                    'doubled' => [false, false],
                    'doubleDrawn' => [false, false],
                    'handMultipliers' => []
                ]);

                $updateRestore();
                return new ContinueGame($game, [
                    'split' => $this->gameData($game)['split'],
                    'player' => $this->gameData($game)['player']
                ]);
            case 'hit':
                if(count($hand) < 2 || $this->getScore($hand) >= 21 || (!empty($doubled[$handIndex]) && !empty($doubleDrawn[$handIndex]))) return $failed();
                $card = $this->nextCard($game);
                $hand = $this->gameData($game)[$handKey];
                array_push($hand, $card);
                $update = [$handKey => $hand];
                if(!empty($doubled[$handIndex])) {
                    $doubleDrawn[$handIndex] = true;
                    $update['doubleDrawn'] = $doubleDrawn;
                }
                $this->pushData($game, $update);
                $updateRestore();
                return new ContinueGame($game, [
                    'player' => $card
                ]);
            case 'double':
                if(count($hand) !== 2 || !empty($doubled[$handIndex])) return $failed();
                if(!$user->balance(Currency::find($game->currency))->demo($game->demo)->subtract($baseWager, Transaction::builder()->game($game->game)->message('Double')->get())) return $failed();
                $doubled[$handIndex] = true;
                $game->update(['wager' => $game->wager + $baseWager]);
                $this->pushData($game, ['doubled' => $doubled]);
                $updateRestore();
                return new ContinueGame($game, []);
            case 'insurance':
                $insuranceStake = $baseWager / 2;
                if(!empty($gameData['insurance']) || $handIndex !== 0 || count($gameData['split'] ?? []) > 0 || count($hand) !== 2 || ($gameData['dealer'][0]['blackjack_value'] ?? null) !== 11 || $game->data['turn'] > 2) return $failed();
                if(!$user->balance(Currency::find($game->currency))->demo($game->demo)->subtract($insuranceStake, Transaction::builder()->game($game->game)->message('Insurance')->get())) return $failed();
                $game->update(['wager' => $game->wager + $insuranceStake]);
                $this->pushData($game, ['insurance' => true, 'insuranceStake' => $insuranceStake]);
                $updateRestore();
                return new ContinueGame($game, []);
            case 'stand':
                if(count($hand) < 2) return $failed();
                $dealer = $gameData['dealer'];
                $dealerScore = $this->getScore($dealer);
                $dealerDraw = $gameData['dealerDraw'] ?? [];
                if(empty($gameData['dealerResolved'])) {
                    while($dealerScore < 17) {
                        $card = $this->nextCard($game);
                        array_push($dealerDraw, $card);
                        array_push($dealer, $card);
                        $dealerScore = $this->getScore($dealer);
                    }
                    $this->pushData($game, ['dealer' => $dealer, 'dealerResolved' => true, 'dealerDraw' => $dealerDraw]);
                }

                $dealerBlackjack = count($dealer) === 2 && $dealerScore === 21;
                $multiplier = $this->handMultiplier($hand, $dealer);
                if(count($gameData['split'] ?? []) > 0 && $handIndex === 0) {
                    $handMultipliers = $gameData['handMultipliers'] ?? [];
                    $handMultipliers[0] = $multiplier;
                    $this->pushData($game, ['handMultipliers' => $handMultipliers, 'currentHand' => 1]);
                    $updateRestore();
                    return new ContinueGame($game, []);
                }

                $hands = count($gameData['split'] ?? []) > 0
                    ? [$gameData['player'], $gameData['split']]
                    : [$gameData['player']];
                $handMultipliers = $gameData['handMultipliers'] ?? [];
                $handMultipliers[$handIndex] = $multiplier;
                $payout = 0;
                foreach($hands as $index => $settledHand) {
                    $settledMultiplier = $handMultipliers[$index] ?? 0;
                    $handWeight = !empty($doubled[$index]) ? 2 : 1;
                    $payout += $baseWager * $handWeight * $settledMultiplier;
                }
                if($dealerBlackjack && !empty($gameData['insurance'])) $payout += floatval($gameData['insuranceStake']) * 3;
                $payout = round($payout, 2);
                $multiplier = $game->wager > 0 ? $payout / $game->wager : 0;
                $dealer[1]['hidden'] = false;
                $this->pushData($game, ['dealer' => $dealer, 'handMultipliers' => $handMultipliers]);
                $game->update(['multiplier' => $multiplier]);
                $updateRestore();
                return new FinishGame($game, [
                    'dealerReveal' => $dealer[1],
                    'dealerDraw' => $dealerDraw
                ]);
        }

        return $failed();
    }

    private function handMultiplier(array $player, array $dealer): float {
        $playerScore = $this->getScore($player);
        $dealerScore = $this->getScore($dealer);
        if($playerScore > 21) return 0;
        $playerNatural = count($player) === 2 && $playerScore === 21;
        $dealerNatural = count($dealer) === 2 && $dealerScore === 21;
        if($dealerNatural) return $playerNatural ? 1 : 0;
        if($playerNatural) return HouseEdgeModule::apply($this, 2.5);
        if($playerScore === $dealerScore) return 1;
        if($dealerScore > 21 || $playerScore > $dealerScore) return HouseEdgeModule::apply($this, 2);
        return 0;
    }

    public function isLoss(ProvablyFairResult $result, \App\Models\Game $game, array $turnData): bool {
        $hand = $this->gameData($game)[$this->gameData($game)['currentHand'] == 0 ? 'player' : 'split'];
        array_push($hand, $this->nextCard($game, false, $result->server_seed()));
        if($this->getScore($hand) < 13) return true;

        return $this->getScore($hand) > 21;
    }

    function result(ProvablyFairResult $result): array {
        return $this->getCards($result, 208);
    }

    private function getScore($hand) {
        $score = 0; $aces = 0;

        foreach ($hand as $value) {
            $score += $value['blackjack_value'];
            if($value['blackjack_value'] == 11) $aces += 1;
            if($score > 21 && $aces > 0) {
                $score -= 10;
                $aces--;
            }
        }

        return $score;
    }

    private function deck() {
        return [
            1 => ['type' => 'spades', 'value' => 'A', 'rank' => 0, 'slot' => 1, 'blackjackValue' => 11],
            2 => ['type' => 'spades', 'value' => '2', 'rank' => 1, 'slot' => 2, 'blackjackValue' => 2],
            3 => ['type' => 'spades', 'value' => '3', 'rank' => 2, 'slot' => 3, 'blackjackValue' => 3],
            4 => ['type' => 'spades', 'value' => '4', 'rank' => 3, 'slot' => 4, 'blackjackValue' => 4],
            5 => ['type' => 'spades', 'value' => '5', 'rank' => 4, 'slot' => 5, 'blackjackValue' => 5],
            6 => ['type' => 'spades', 'value' => '6', 'rank' => 5, 'slot' => 6, 'blackjackValue' => 6],
            7 => ['type' => 'spades', 'value' => '7', 'rank' => 6, 'slot' => 7, 'blackjackValue' => 7],
            8 => ['type' => 'spades', 'value' => '8', 'rank' => 7, 'slot' => 8, 'blackjackValue' => 8],
            9 => ['type' => 'spades', 'value' => '9', 'rank' => 8, 'slot' => 9, 'blackjackValue' => 9],
            10 => ['type' => 'spades', 'value' => '10', 'rank' => 9, 'slot' => 10, 'blackjackValue' => 10],
            11 => ['type' => 'spades', 'value' => 'J', 'rank' => 10, 'slot' => 11, 'blackjackValue' => 10],
            12 => ['type' => 'spades', 'value' => 'Q', 'rank' => 11, 'slot' => 12, 'blackjackValue' => 10],
            13 => ['type' => 'spades', 'value' => 'K', 'rank' => 12, 'slot' => 13, 'blackjackValue' => 10],
            14 => ['type' => 'hearts', 'value' => 'A', 'rank' => 0, 'slot' => 1, 'blackjackValue' => 11],
            15 => ['type' => 'hearts', 'value' => '2', 'rank' => 1, 'slot' => 2, 'blackjackValue' => 2],
            16 => ['type' => 'hearts', 'value' => '3', 'rank' => 2, 'slot' => 3, 'blackjackValue' => 3],
            17 => ['type' => 'hearts', 'value' => '4', 'rank' => 3, 'slot' => 4, 'blackjackValue' => 4],
            18 => ['type' => 'hearts', 'value' => '5', 'rank' => 4, 'slot' => 5, 'blackjackValue' => 5],
            19 => ['type' => 'hearts', 'value' => '6', 'rank' => 5, 'slot' => 6, 'blackjackValue' => 6],
            20 => ['type' => 'hearts', 'value' => '7', 'rank' => 6, 'slot' => 7, 'blackjackValue' => 7],
            21 => ['type' => 'hearts', 'value' => '8', 'rank' => 7, 'slot' => 8, 'blackjackValue' => 8],
            22 => ['type' => 'hearts', 'value' => '9', 'rank' => 8, 'slot' => 9, 'blackjackValue' => 9],
            23 => ['type' => 'hearts', 'value' => '10', 'rank' => 9, 'slot' => 10, 'blackjackValue' => 10],
            24 => ['type' => 'hearts', 'value' => 'J', 'rank' => 10, 'slot' => 11, 'blackjackValue' => 10],
            25 => ['type' => 'hearts', 'value' => 'Q', 'rank' => 11, 'slot' => 12, 'blackjackValue' => 10],
            26 => ['type' => 'hearts', 'value' => 'K', 'rank' => 12, 'slot' => 13, 'blackjackValue' => 10],
            27 => ['type' => 'clubs', 'value' => 'A', 'rank' => 0, 'slot' => 1, 'blackjackValue' => 11],
            28 => ['type' => 'clubs', 'value' => '2', 'rank' => 1, 'slot' => 2, 'blackjackValue' => 2],
            29 => ['type' => 'clubs', 'value' => '3', 'rank' => 2, 'slot' => 3, 'blackjackValue' => 3],
            30 => ['type' => 'clubs', 'value' => '4', 'rank' => 3, 'slot' => 4, 'blackjackValue' => 4],
            31 => ['type' => 'clubs', 'value' => '5', 'rank' => 4, 'slot' => 5, 'blackjackValue' => 5],
            32 => ['type' => 'clubs', 'value' => '6', 'rank' => 5, 'slot' => 6, 'blackjackValue' => 6],
            33 => ['type' => 'clubs', 'value' => '7', 'rank' => 6, 'slot' => 7, 'blackjackValue' => 7],
            34 => ['type' => 'clubs', 'value' => '8', 'rank' => 7, 'slot' => 8, 'blackjackValue' => 8],
            35 => ['type' => 'clubs', 'value' => '9', 'rank' => 8, 'slot' => 9, 'blackjackValue' => 9],
            36 => ['type' => 'clubs', 'value' => '10', 'rank' => 9, 'slot' => 10, 'blackjackValue' => 10],
            37 => ['type' => 'clubs', 'value' => 'J', 'rank' => 10, 'slot' => 11, 'blackjackValue' => 10],
            38 => ['type' => 'clubs', 'value' => 'Q', 'rank' => 11, 'slot' => 12, 'blackjackValue' => 10],
            39 => ['type' => 'clubs', 'value' => 'K', 'rank' => 12, 'slot' => 13, 'blackjackValue' => 10],
            40 => ['type' => 'diamonds', 'value' => 'A', 'rank' => 0, 'slot' => 1, 'blackjackValue' => 11],
            41 => ['type' => 'diamonds', 'value' => '2', 'rank' => 1, 'slot' => 2, 'blackjackValue' => 2],
            42 => ['type' => 'diamonds', 'value' => '3', 'rank' => 2, 'slot' => 3, 'blackjackValue' => 3],
            43 => ['type' => 'diamonds', 'value' => '4', 'rank' => 3, 'slot' => 4, 'blackjackValue' => 4],
            44 => ['type' => 'diamonds', 'value' => '5', 'rank' => 4, 'slot' => 5, 'blackjackValue' => 5],
            45 => ['type' => 'diamonds', 'value' => '6', 'rank' => 5, 'slot' => 6, 'blackjackValue' => 6],
            46 => ['type' => 'diamonds', 'value' => '7', 'rank' => 6, 'slot' => 7, 'blackjackValue' => 7],
            47 => ['type' => 'diamonds', 'value' => '8', 'rank' => 7, 'slot' => 8, 'blackjackValue' => 8],
            48 => ['type' => 'diamonds', 'value' => '9', 'rank' => 8, 'slot' => 9, 'blackjackValue' => 9],
            49 => ['type' => 'diamonds', 'value' => '10', 'rank' => 9, 'slot' => 10, 'blackjackValue' => 10],
            50 => ['type' => 'diamonds', 'value' => 'J', 'rank' => 10, 'slot' => 11, 'blackjackValue' => 10],
            51 => ['type' => 'diamonds', 'value' => 'Q', 'rank' => 11, 'slot' => 12, 'blackjackValue' => 10],
            52 => ['type' => 'diamonds', 'value' => 'K', 'rank' => 12, 'slot' => 13, 'blackjackValue' => 10]
        ];
    }

    public function getBotTurnData($turnId): array {
        return [
            'type' => mt_rand(0, 100) <= 60 ? 'stand' : 'hit'
        ];
    }

}
