<?php

namespace App\Services;

use App\Models\Game;
use App\Models\User;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Server-only adapter for Supabase Auth and PostgREST.
 *
 * Browser clients never receive the service-role key. Laravel keeps issuing
 * its existing Sanctum token so the legacy game engine can continue to use
 * the same authenticated API contract during the migration.
 */
class SupabaseService {
    private $client;

    public function __construct() {
        $this->client = new Client(['http_errors' => false, 'timeout' => 10]);
    }

    public function enabled(): bool {
        return (bool) config('supabase.url') && (bool) config('supabase.anon_key') && (bool) config('supabase.service_role_key');
    }

    public function signUp(string $email, string $password, string $name): ?array {
        return $this->request('POST', '/auth/v1/signup', config('supabase.anon_key'), [
            'email' => $email,
            'password' => $password,
            'data' => ['display_name' => $name]
        ]);
    }

    public function signIn(string $email, string $password): ?array {
        return $this->request('POST', '/auth/v1/token?grant_type=password', config('supabase.anon_key'), [
            'email' => $email,
            'password' => $password
        ]);
    }

    public function upsertProfile(User $user): void {
        if (!$this->enabled() || !$user->supabase_id) return;

        $this->request('POST', '/rest/v1/profiles?on_conflict=id', config('supabase.service_role_key'), [[
            'id' => $user->supabase_id,
            'display_name' => $user->name,
            'updated_at' => now()->toIso8601String()
        ]], ['Prefer' => 'resolution=merge-duplicates,return=minimal']);
    }

    public function recordGame(Game $game, User $user): void {
        if (!$this->enabled() || !$user->supabase_id) return;

        $this->request('POST', '/rest/v1/game_rounds', config('supabase.service_role_key'), [[
            'legacy_game_id' => (string) $game->_id,
            'user_id' => $user->supabase_id,
            'game_slug' => $game->game,
            'wager' => (float) $game->wager,
            'profit' => (float) $game->profit,
            'multiplier' => (float) $game->multiplier,
            'currency' => $game->currency,
            'status' => $game->status,
            'played_at' => now()->toIso8601String()
        ]], ['Prefer' => 'return=minimal']);
    }

    private function request(string $method, string $path, ?string $key, array $body = [], array $headers = []): ?array {
        if (!$this->enabled() || !$key) return null;

        try {
            $response = $this->client->request($method, config('supabase.url') . $path, [
                'headers' => array_merge([
                    'apikey' => $key,
                    'Authorization' => 'Bearer ' . $key,
                    'Content-Type' => 'application/json'
                ], $headers),
                'json' => $body
            ]);

            if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) return null;
            $content = (string) $response->getBody();
            return $content === '' ? [] : json_decode($content, true);
        } catch (GuzzleException $exception) {
            report($exception);
            return null;
        }
    }
}
