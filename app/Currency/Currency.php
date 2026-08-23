<?php namespace App\Currency;

use App\Models\User;

/**
 * Virtual play-credit definition. RATINO has no deposits, withdrawals,
 * exchanges, blockchain wallets, or third-party payment providers.
 */
abstract class Currency {
    abstract function id(): string;
    abstract function walletId(): string;
    abstract function name(): string;
    abstract function displayName(): string;
    abstract function icon(): string;
    abstract function style(): string;

    public function minBet(): float { return 1; }
    public function tokenPrice(): float { return 1; }
    public function isRunning(): bool { return true; }

    public static function toCurrencyArray(array $currencies): array {
        $result = [];
        foreach ($currencies as $currency) {
            $result[$currency->id()] = [
                'id' => $currency->id(),
                'walletId' => $currency->walletId(),
                'name' => $currency->name(),
                'displayName' => $currency->displayName(),
                'icon' => $currency->icon(),
                'style' => $currency->style(),
                'price' => 1,
                'balance' => [
                    'real' => auth('sanctum')->guest() ? 0 : auth('sanctum')->user()->balance($currency)->get(),
                    'demo' => auth('sanctum')->guest() ? 0 : auth('sanctum')->user()->balance($currency)->demo()->get()
                ]
            ];
        }
        return $result;
    }

    public static function all(): array { return [new Credits()]; }
    public static function getAllSupportedCoins(): array { return self::all(); }

    public static function find(string $id): ?Currency {
        foreach (self::all() as $currency) if ($currency->id() === $id) return $currency;
        return null;
    }
}
