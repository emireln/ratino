<?php

namespace App\Models;

use App\Currency\Currency;
use App\Events\BalanceModification;
use App\Services\VipService;
use App\Token\NewAccessToken;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\BSON\Decimal128;
use MongoDB\BSON\ObjectId;
use MongoDB\Operation\FindOneAndUpdate;
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

    // `_id` reads back as a string; raw collection filters need the stored ObjectId.
    public function bsonId(): ObjectId {
        return new ObjectId((string) $this->_id);
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

    public function add(float $amount, ?array $transaction = null): bool {
        return $this->addConditionally($amount, [], [], $transaction);
    }

    public function addIfBelow(float $amount, float $limit, ?array $transaction = null): bool {
        if(!is_finite($limit) || $limit < 0) return false;
        return $this->addConditionally($amount, [
            $this->getColumn() => ['$lt' => new Decimal128(number_format($limit, 2, '.', ''))]
        ], [], $transaction);
    }

    public function addConditionally(float $amount, array $conditions, array $set, ?array $transaction = null): bool {
        $amount = $this->normalizeAmount($amount);
        if ($amount === null) return false;

        $column = $this->getColumn();
        $update = ['$inc' => [$column => new Decimal128(number_format($amount, 2, '.', ''))]];
        if($set) $update['$set'] = $set;
        $updated = DB::connection('mongodb')->getCollection($this->user->getTable())->findOneAndUpdate(
            array_merge(['_id' => $this->user->bsonId()], $conditions),
            $update,
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        if ($updated === null) return false;

        $new = $this->decimalValue($updated->{$column} ?? 0);
        $old = round($new - $amount, 2);
        $this->user->setAttribute($column, $updated->{$column});

        Transaction::create([
            'user' => $this->user->_id,
            'demo' => $this->demo,
            'currency' => $this->currency->id(),
            'new' => $new,
            'old' => $old,
            'amount' => $amount,
            'quiet' => $this->quiet,
            'data' => $transaction ?? []
        ]);
        if (!$this->quiet) event(new BalanceModification($this->user, $this->currency, 'add', $this->demo, $amount, 0));
        return true;
    }

    public function subtract(float $amount, ?array $transaction = null): bool {
        $amount = $this->normalizeAmount($amount);
        if ($amount === null) return false;

        $column = $this->getColumn();
        $updated = DB::connection('mongodb')->getCollection($this->user->getTable())->findOneAndUpdate(
            ['_id' => $this->user->bsonId(), $column => ['$gte' => new Decimal128(number_format($amount, 2, '.', ''))]],
            ['$inc' => [$column => new Decimal128(number_format(-$amount, 2, '.', ''))]],
            ['returnDocument' => FindOneAndUpdate::RETURN_DOCUMENT_AFTER]
        );
        if ($updated === null) return false;

        $new = $this->decimalValue($updated->{$column} ?? 0);
        $old = round($new + $amount, 2);
        $this->user->setAttribute($column, $updated->{$column});

        Transaction::create([
            'user' => $this->user->_id,
            'demo' => $this->demo,
            'currency' => $this->currency->id(),
            'new' => $new,
            'old' => $old,
            'amount' => -$amount,
            'quiet' => $this->quiet,
            'data' => $transaction ?? []
        ]);
        if (!$this->quiet) event(new BalanceModification($this->user, $this->currency, 'subtract', $this->demo, $amount, 0));
        return true;
    }

    private function normalizeAmount(float $amount): ?float {
        if (!is_finite($amount) || $amount <= 0) return null;
        $amount = round($amount, 2);
        return $amount > 0 ? $amount : null;
    }

    private function decimalValue($value): float {
        return $value instanceof Decimal128
            ? floatval($value->jsonSerialize()['$numberDecimal'])
            : floatval($value);
    }

}
