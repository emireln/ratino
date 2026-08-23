<?php

namespace App\Models;

use App\Currency\Currency;
use App\Events\BalanceModification;
use App\Services\VipService;
use App\Token\NewAccessToken;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\BSON\Decimal128;
use MongoDB\Laravel\Auth\User as Authenticatable;

class User extends Authenticatable {

    use Notifiable, HasApiTokens;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'avatar', 'access', 'client_seed',
        'name_history', 'latest_activity',
        'ban', 'mute',
        'register_ip', 'login_ip',
        'favoriteGames',

        'supabase_id',

        'credits', 'demo_credits',

        'vip_wager', 'vip_rakeback', 'vip_milestones',
        'heist_claimed', 'bounties', 'streaks', 'last_activity', 'weekly_drop_claimed',
        'friends', 'friend_requests', 'active_game', 'active_room'
    ];

    protected $hidden = [
        'password', 'remember_token', 'email', 'ban', 'mute',
        'register_ip', 'login_ip',
        'vip_wager', 'vip_rakeback', 'vip_milestones',
        'heist_claimed', 'bounties', 'streaks'
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'mute' => 'datetime',
        'latest_activity' => 'datetime',
        'name_history' => 'json',
        'favoriteGames' => 'json',
        'vip_milestones' => 'json',
        'heist_claimed' => 'datetime',
        'weekly_drop_claimed' => 'datetime',
        'bounties' => 'json',
        'streaks' => 'json',
        'last_activity' => 'datetime',
        'friends' => 'json',
        'friend_requests' => 'json'
    ];

    public static function getIp() {
        foreach (array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR') as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) return $ip;
                }
            }
        }
        return request()->ip();
    }

    public function balance(Currency $currency): UserBalance {
        return new UserBalance($this, $currency);
    }

    public function clientCurrency(): Currency { return Currency::all()[0]; }

    public function vipLevel(): int {
        return VipService::tierIndex(VipService::wager($this));
    }

    public function createToken(string $name = 'web', array $abilities = ['*']) {
        $token = $this->tokens()->create([
            'name' => $name,
            'token' => hash('sha256', $plainTextToken = Str::random(80)),
            'abilities' => $abilities,
        ]);

        return new NewAccessToken($token, $token->id.'|'.$plainTextToken);
    }

}

class UserBalance {

    private User $user;
    private Currency $currency;
    private bool $quiet = false;
    private bool $demo = false;

    private float $minValue = 0.00000000;

    public function __construct(User $user, Currency $currency) {
        $this->user = $user;
        $this->currency = $currency;
    }

    public function quiet() {
        $this->quiet = true;
        return $this;
    }

    public function demo($set = true) {
        $this->demo = $set;
        return $this;
    }

    public function get(): float {
        $raw = $this->user->{$this->getColumn()} ?? new Decimal128($this->minValue);
        $value = $raw instanceof Decimal128 ? floatval($raw->jsonSerialize()['$numberDecimal']) : floatval($raw);
        return $value < 0 ? 0 : floatval(number_format($value, 2, '.', ''));
    }

    private function getColumn() {
        return $this->demo ? 'demo_credits' : 'credits';
    }

    public function add(float $amount, array $transaction = null) {
        $this->user->update([
            $this->getColumn() => new Decimal128(strval($this->get() + $amount))
        ]);

        if ($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'add', $this->demo, $amount, 0));
        Transaction::create([
            'user' => $this->user->_id,
            'demo' => $this->demo,
            'currency' => $this->currency->id(),
            'new' => $this->get(),
            'old' => $this->get() - $amount,
            'amount' => $amount,
            'quiet' => $this->quiet,
            'data' => $transaction ?? []
        ]);
    }

    public function subtract(float $amount, array $transaction = null) {
        $value = $this->get() - $amount;
        if ($value < 0) $value = 0;
        $this->user->update([
            $this->getColumn() => new Decimal128(strval($value))
        ]);

        if ($this->quiet == false) event(new BalanceModification($this->user, $this->currency, 'subtract', $this->demo, $amount, 0));
        Transaction::create([
            'user' => $this->user->_id,
            'demo' => $this->demo,
            'currency' => $this->currency->id(),
            'new' => $this->get(),
            'old' => $this->get() + $amount,
            'amount' => -$amount,
            'quiet' => $this->quiet,
            'data' => $transaction ?? []
        ]);
    }

}
