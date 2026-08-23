<?php

namespace App\Http\Controllers\Api;

use App\Games\Kernel\ProvablyFair;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\SupabaseService;
use App\Utils\APIResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use MongoDB\BSON\Decimal128;

class AuthController extends Controller {

    private function createRatinoUser(string $email, string $name, string $password, array $extra = []): User {
        return User::create(array_merge([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'avatar' => '/avatar/' . uniqid(),
            'access' => 'user',
            'client_seed' => ProvablyFair::generateServerSeed(),
            'credits' => new Decimal128('10000'),
            'demo_credits' => new Decimal128('10000'),
            'name_history' => [['time' => Carbon::now(), 'name' => $name]],
            'register_ip' => User::getIp(),
            'login_ip' => User::getIp()
        ], $extra));
    }

    public function update() {
        return APIResponse::success(auth('sanctum')->user()->toArray());
    }

    public function login(Request $request) {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string', 'min:4']
        ]);

        $user = User::where('email', $request->login)->orWhere('name', $request->login)->first();
        $supabase = app(SupabaseService::class);

        if ($supabase->enabled()) {
            if (!$user || !$user->email) return APIResponse::reject(1, 'Wrong credentials');
            $session = $supabase->signIn($user->email, $request->password);
            if (!$session || !isset($session['user']['id'])) return APIResponse::reject(1, 'Wrong credentials');
            if (!$user->supabase_id) $user->update(['supabase_id' => $session['user']['id']]);
            if ($user->supabase_id !== $session['user']['id']) return APIResponse::reject(1, 'Wrong credentials');
        } elseif (!$user || !Hash::check($request->password, $user->password)) return APIResponse::reject(1, 'Wrong credentials');

        $user->update(['login_ip' => User::getIp(), 'last_activity' => now()]);

        return APIResponse::success([
            'user' => $user->toArray(),
            'token' => $user->createToken('web')->plainTextToken
        ]);
    }

    public function register(Request $request) {
        $request->validate([
            'email' => ['required', 'unique:users', 'email'],
            'name' => ['required', 'unique:users', 'string', 'max:64', 'regex:/^[a-zA-Z0-9]{4,64}$/u'],
            'password' => ['required', 'string', 'min:8']
        ]);

        $supabase = app(SupabaseService::class);
        $extra = [];
        if ($supabase->enabled()) {
            $remoteUser = $supabase->signUp($request->email, $request->password, $request->name);
            if (!$remoteUser || !isset($remoteUser['user']['id'])) return APIResponse::reject(2, 'Unable to create account');
            $extra['supabase_id'] = $remoteUser['user']['id'];
        }

        $user = $this->createRatinoUser($request->email, $request->name, $request->password, $extra);
        $supabase->upsertProfile($user);

        return APIResponse::success([
            'user' => $user->toArray(),
            'token' => $user->createToken('web')->plainTextToken
        ]);
    }

    public function logout(Request $request) {
        $user = $request->user('sanctum');
        if ($user && $user->currentAccessToken()) $user->currentAccessToken()->delete();
        return APIResponse::success();
    }

}
