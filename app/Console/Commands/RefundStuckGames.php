<?php

namespace App\Console\Commands;

use App\Currency\Currency;
use App\Models\Game;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Games created before the sequential-id fix carry a numeric 'id' the client can never send back,
 * so Turn/Finish reject them and their stake is stuck. Lists them by default; --force refunds.
 */
class RefundStuckGames extends Command {

    protected $signature = 'game:refund-stuck {--force : Cancel and refund the stuck games instead of only listing them}';

    protected $description = 'List or refund in-progress games left behind by the numeric game id bug';

    public function handle(): int {
        $docs = DB::connection('mongodb')->getCollection('games')->find([
            'status' => 'in-progress',
            'settled' => ['$ne' => true],
            'id' => ['$exists' => true],
        ]);

        $count = 0;
        foreach ($docs as $doc) {
            $count++;
            $this->line(sprintf('%s %s %s wager=%s user=%s demo=%s', (string) $doc['_id'], $doc['game'] ?? '?', $doc['type'] ?? '?', $doc['wager'] ?? '?', (string) ($doc['user'] ?? ''), json_encode($doc['demo'] ?? false)));
            if ($this->option('force')) $this->refund((string) $doc['_id']);
        }

        $this->info($count . ' stuck game(s)' . ($this->option('force') ? ' processed.' : '. Re-run with --force to refund them.'));
        return self::SUCCESS;
    }

    private function refund(string $gameId): void {
        // Conditional cancel: a game settled by a concurrent request is skipped, not refunded twice.
        $cancelled = Game::where('_id', $gameId)->where('status', 'in-progress')->where('settled', '!=', true)
            ->update(['status' => 'cancelled', 'settled' => true]);
        if ($cancelled === 0) return;

        $game = Game::where('_id', $gameId)->first();
        $user = User::where('_id', (string) $game->user)->first();
        if ($user == null) return;

        $user->balance(Currency::find($game->currency))->demo($game->demo)
            ->add($game->wager, Transaction::builder()->game($game->game)->message('Refund (stuck legacy game)')->get());
    }

}
