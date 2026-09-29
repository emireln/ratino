# RATINO

Modern rebuild of the RATINO (old project) casino platform.

| Layer | Stack |
|---|---|---|
| Backend | Laravel 7 / PHP 7.4 (EOL) | **Laravel 12 / PHP 8.5** |
| Database driver | jenssegers/mongodb (unmaintained) | **mongodb/laravel-mongodb 5** |
| Frontend | Vue 2 + Vuex + webpack 4 (EOL) | **Vue 3 + Pinia + Vite 6** |
| Realtime | laravel-echo-server + Redis polling | **Laravel Reverb** (native WebSocket, presence channels) |
| Auth | Sanctum + RSA whisper tokens | **Sanctum bearer tokens over MongoDB** |

## Requirements

- PHP 8.2+ with ext-mongodb (dev machine used PHP 8.5 + mongodb 2.4.0)
- Composer
- Node 18+
- MongoDB

## Run (development)

```bash
# 1. MongoDB
mongod --dbpath <data dir>

# 2. Backend API (port 8000)
composer install
cp .env.example .env        # set DB_CONNECTION=mongodb + MONGO_DB_*, BROADCAST_CONNECTION=reverb
php artisan key:generate
php artisan serve --host=127.0.0.1 --port=8000

# 3. WebSocket server for realtime chat/rooms/live feed (port 8080)
php artisan reverb:start

# 4. Queue worker (multiplayer round chains)
php artisan queue:work

# 5. Frontend dev server (port 5173, proxies /api + /auth to :8000)
cd frontend && npm install && npm run dev
```

Production build (`npm run build`) emits into `../public` — the SPA is served by Laravel
(catch-all route returns the built `public/index.html`; static assets served directly).
Do NOT enable `emptyOutDir` in vite.config.js — it would delete `public/index.php`.

Multiplayer chains: `php artisan game:chain all`. Rain drops: scheduled (`win5x:rain`).

## What's inside

- **18 provably-fair games** (HMAC-SHA256 server seed / client seed / nonce), ported verbatim:
  Mines, Dice, Wheel, Plinko, Coinflip, Tower, Keno, Stairs, Blackjack, Diamonds,
  Roulette (custom physics & ball landing), Crash, HiLo, Limbo, Slide, Baccarat, VideoPoker, Slots.
- **Vault & Weekly Drops**: real virtual Chips wallet, +5,000 Chips weekly drop with countdown, emergency refill faucet, and practice reloads.
- **Friends & Multiplayer Rooms**: add friends by username, live online/activity indicators, 1-click private room invites (`/room/:code`) with real-time WebSocket toast alerts.
- **Filtered Live Bets**: realtime global live bets feed with tabs for All Bets, High Rollers, Lucky Multipliers (10x+), and Friends Only bets.
- **Fast Floating Reactions**: animated emoji reaction dock (`🔥`, `🚀`, `💎`, `🤑`, `👏`, `💀`, `🤯`, `❤️`) that drops floating physics-driven reactions across player screens in real-time.
- **Favorites, Auth & Profiles**: star favorite games, dedicated `/favorites` view, auth-gated game access, profile customization modal (username change, 8 curated casino avatars, custom image upload to `/storage/avatars/`).
- **Supabase Production Schema**: complete SQL schema in `database/supabase/schema.sql` with Row Level Security (RLS) policies on `profiles`, `game_rounds`, `rooms`, `transactions`, `social_notifications`, triggers (`handle_new_user`), and secure avatar storage bucket.
- **Realtime rooms**: presence channels per chat room (global + private rooms),
  `/tip username amount`, user-funded rain, live bets feed pushed on every finished game,
  live balance updates on a private channel.
- **VIP club**: 7 VIP tiers (Bronze to Legend), instant rakeback accrual on every bet, auto milestone payouts.
- **Daily Vault Heist** wheel (24h cooldown), **daily/weekly bounties** with automatic
  verification + payout, **leaderboards** (daily/weekly/all-time × wager/multiplier),
  **session stats** with live profit graph.
- **BYOK AI assistant** — Gemini/OpenAI key stays in the browser; direct provider calls only.

## Asset Workflow

- Always place new images, banners, logos, and audio in `frontend/public/`.
- `frontend/public/` is the authoritative source of truth. When running `npm run build`, Vite distributes the assets into `public/`.
- Run `php scripts/make-favicon.php` to rebuild crisp, centered multi-resolution branded favicons.

## Security posture (carried over + hardened)

- Whisper ownership checks (players can only act on their own in-progress games) + ObjectId validation
- Rate limits: login/register 10/min, whisper + data APIs 120/min, update 60/min
- CORS from `CORS_ALLOWED_ORIGINS` env (empty = same-origin only)
- Global security-headers middleware; no seed-management UI (provably fair is server-side)
- Server seeds never broadcast before game end; multiplayer bet payloads strip seeds

## Notes

- Optional Supabase auth + game-round ledger: set SUPABASE_* env vars (schema in database/supabase/schema.sql).
