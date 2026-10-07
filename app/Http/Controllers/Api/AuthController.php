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
            'password' => ['required', 'string']
        ]);

        $user = User::where('email', $request->login)->orWhere('name', $request->login)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return APIResponse::reject(1, 'Invalid login or password.');
        }

        $supabase = app(SupabaseService::class);
        if ($supabase->enabled()) {
            try {
                if (!$user->supabase_id && $user->email) {
                    $remote = $supabase->signUp($user->email, $request->password, $user->name);
                    $remoteId = $remote['id'] ?? ($remote['user']['id'] ?? null);
                    if ($remoteId) {
                        $user->update(['supabase_id' => $remoteId]);
                    }
                }
            } catch (\Throwable $t) {}
        }

        $user->update(['login_ip' => User::getIp(), 'last_activity' => now()]);

        return APIResponse::success([
            'user' => $user->toArray(),
            'token' => $user->createToken('web')->plainTextToken
        ]);
    }

    public function register(Request $request) {
        $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'min:3', 'max:32', 'regex:/^[a-zA-Z0-9_-]{3,32}$/u'],
            'password' => ['required', 'string', 'min:8']
        ]);

        if (User::where('email', $request->email)->exists()) {
            return APIResponse::reject(2, 'This email is already registered.');
        }

        if (User::where('name', $request->name)->exists()) {
            return APIResponse::reject(3, 'This username is already taken.');
        }

        $extra = [];
        $supabase = app(SupabaseService::class);
        if ($supabase->enabled()) {
            try {
                $remote = $supabase->signUp($request->email, $request->password, $request->name);
                $remoteId = $remote['id'] ?? ($remote['user']['id'] ?? null);
                if ($remoteId) {
                    $extra['supabase_id'] = $remoteId;
                }
            } catch (\Throwable $t) {}
        }

        $user = $this->createRatinoUser($request->email, $request->name, $request->password, $extra);

        if ($supabase->enabled() && isset($extra['supabase_id'])) {
            try {
                $supabase->upsertProfile($user);
            } catch (\Throwable $t) {}
        }

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

    public function editProfile(Request $request) {
        $user = $request->user('sanctum');
        if (!$user) return APIResponse::reject(1, 'Not authorized');

        $updates = [];

        // 1. Username update
        if ($request->has('name') && !empty($request->name)) {
            $newName = trim($request->name);
            if ($newName !== $user->name) {
                $request->validate([
                    'name' => ['required', 'string', 'min:4', 'max:64', 'regex:/^[a-zA-Z0-9_-]{4,64}$/u']
                ]);

                $existing = User::where('name', $newName)->where('_id', '!=', $user->_id)->first();
                if ($existing) {
                    return APIResponse::reject(2, 'Username already taken');
                }

                $updates['name'] = $newName;
                $history = $user->name_history ?? [];
                $history[] = ['time' => Carbon::now()->toIso8601String(), 'name' => $newName];
                $updates['name_history'] = $history;
            }
        }

        // 2. Custom Avatar File Upload
        if ($request->hasFile('avatar_file')) {
            $request->validate([
                'avatar_file' => ['required', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120']
            ]);

            $file = $request->file('avatar_file');
            $filename = 'avatar_' . $user->_id . '_' . time() . '.' . $file->getClientOriginalExtension();
            
            // Ensure directory exists in public/storage/avatars
            $destinationPath = public_path('storage/avatars');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }
            $file->move($destinationPath, $filename);

            $updates['avatar'] = '/storage/avatars/' . $filename;
        } elseif ($request->has('avatar') && !empty($request->avatar)) {
            // Preset avatar URL / path
            $updates['avatar'] = $request->avatar;
        }

        if (!empty($updates)) {
            $user->update($updates);
            
            $supabase = app(SupabaseService::class);
            if ($supabase->enabled()) {
                $supabase->upsertProfile($user);
            }
        }

        return APIResponse::success($user->fresh()->toArray());
    }

}
