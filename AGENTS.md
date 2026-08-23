# AGENTS.md — RATINO

Casino platform for friends. Laravel 12 + PHP 8.5 backend, MongoDB, Vue 3 + Pinia + Vite frontend, Laravel Reverb realtime. Single virtual currency: CHIPS. No banking, no crypto, no payments.

## Talk (caveman)

Terse. Drop filler: just/really/basically/sure/happy. No preamble, no tool-call narration. Fragments OK. Short over long: "fix", not "implement a solution for".

Keep exact: code, errors, API names, commands, numbers, units. Never drop not/never/no/only/except. No invented abbreviations. Never name the style.

Errors quoted exact. Long logs: quote shortest decisive line, not dump.

Full prose for: security warnings, irreversible actions, multi-step sequences where fragments misread.

Persisted text (commits, comments, docs, tickets): normal prose. This file exempt — instruction file, stays terse.

## Build (ponytail)

Understand first, then ladder:

1. Need this at all? (YAGNI)
2. Already in repo? Reuse helper/util/pattern.
3. Stdlib does it? Use it.
4. Native platform feature? Use it.
5. Installed dependency? Use it.
6. One line? One line.
7. Then: minimum that works.

Bug fix = root cause. Grep all callers. Fix shared function once, not one caller.

No abstraction not requested. No new dependency. No boilerplate. Deletion > addition. Boring > clever. Fewest files. Shortest working diff that actually fixes.

Deliberate corner-cuts get `ponytail:` comment naming ceiling + upgrade path.

Never lazy about: understanding (read full flow first), validation at trust boundaries, error handling that prevents data loss, security, accessibility, anything explicitly requested.

Non-trivial logic leaves ONE runnable check (assert self-check or one small test; no framework). Trivial: none.

## Run

```bash
mongod --dbpath <data>          # MongoDB
php artisan serve               # API :8000
php artisan reverb:start        # WebSocket :8080
php artisan queue:work          # multiplayer chains + rain
php artisan game:chain all      # start multiplayer round chains
cd frontend && npm run dev      # SPA :5173 (dev; proxies /api to :8000)
cd frontend && npm run build    # prod: emits into ../public (SPA shell)
```

Queue uses sqlite (DB_QUEUE_CONNECTION=sqlite); app data lives in MongoDB.

## Repo map

- `app/Models/` — User (mongo + Sanctum tokens via custom PersonalAccessToken), Game, Chat, Room, Transaction, Settings, Modules, DisabledGame, MultiplayerGameState.
- `app/Games/Kernel/` — provably-fair engine. `Game` abstract: `metadata()`, `process(Data)`, `result(ProvablyFairResult)`. Subtypes: QuickGame, ExtendedGame, MultiplayerGame. `Game::list()` registers the 18 games.
- `app/Whisper/` — POST /api/whisper handlers (Play, Turn, Finish, GameData, RestoreData, Vip, ChatMessage, ChatHistory, Rain, Heist, Bounties, Leaderboard, LiveFeed, Room, Wallet). Registered in `Whisper::list()`.
- `app/Services/` — VipService, BountyService, HeistService, SupabaseService (optional auth + game_rounds ledger).
- `app/Events/` — Reverb broadcasts: ChatMessage (presence `chat.{channel}`), LiveBet (`live`), BalanceModification (private `user.{id}`), Multiplayer* (`multiplayer.{game}`).
- `app/Jobs/Multiplayer/` — round chain jobs. `app/Console/Commands/` — Chain, Rain.
- `frontend/src/` — Vue 3 SPA. `composables/useCasino.js` = global game mixin (whisper, pushStats, resultPopup, sendTurn, finishExtended, $store shim). `lib/echo.js` = Reverb bridge that emits bus events to GameView, which forwards to `multiplayerEvent()` on the active game.
- `frontend/public/` — static assets (img, sounds, webfonts, favicons) — SOURCE OF TRUTH; copied to `public/` at build. Always drop assets here.
- `scripts/make-favicon.php` — regenerates favicons (uses Midstar font when present in `frontend/public/webfonts/`, Arial Bold fallback otherwise).
- Vault & Weekly Drops: `WalletWhisper` handles 5,000 Chips weekly drops, faucet refills, demo reloads.
- Friend Rooms: `RoomWhisper` handles private rooms, codes, member tracking, and room sharing.
- Favorites & Auth: Star favorite games (`/favorites`), guest lock overlay on game views.

## Contracts

- Game components expose: `getSidebarComponents()`, `getClientData()`, `callback(response)`, plus `multiplayerEvent(name, payload)` for multiplayer games.
- Whisper responses: success = plain array (HTTP 200); error = `{code, message}` (HTTP 200 too — APIResponse::success wraps the whisper array).
- Balances: `Decimal128` on `credits` / `demo_credits`. All money via `UserBalance` (add/subtract → Transaction + BalanceModification event).
- Provably fair: HMAC-SHA256(server_seed, "client_seed:nonce:round"). Never break it — client verifier at `frontend/src/fairness/`.

## Repo rules

- PHP 8.5 / Laravel 12 idioms. No PHP 7-era workarounds unless required by a copied kernel file.
- MongoDB via mongodb/laravel-mongodb 5 — Eloquent models with `$connection = 'mongodb'`.
- No new composer/npm deps without reason.
- Seed management UI: intentionally absent. Server seeds fixed per account.
- Security invariants: Turn/Finish whispers own only the caller's games (ownership check + 24-hex ObjectId validation); auth throttled (login/register 10/min), whisper + data APIs throttled (120/min); CORS from `CORS_ALLOWED_ORIGINS`; `SecurityHeaders` middleware global; server seeds never broadcast before round end. Don't remove the checks, don't widen limits without reason.
- `.agents/skills/` — caveman skill suite (installed via `npx skills add JuliusBrussee/caveman`). Use them.
- Favicon/logos: if the Midstar font is dropped into `frontend/public/webfonts/`, rerun `php scripts/make-favicon.php` to render branded favicons.