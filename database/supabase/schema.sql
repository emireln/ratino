-- Run this in the Supabase SQL editor before adding the three SUPABASE_* vars.
-- Auth identities belong to auth.users; application data stays in public.

create table if not exists public.profiles (
    id uuid primary key references auth.users(id) on delete cascade,
    display_name text not null check (char_length(display_name) between 4 and 64),
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

create table if not exists public.game_rounds (
    id bigint generated always as identity primary key,
    legacy_game_id text not null unique,
    user_id uuid not null references auth.users(id) on delete cascade,
    game_slug text not null,
    wager numeric(20, 8) not null check (wager >= 0),
    profit numeric(20, 8) not null,
    multiplier numeric(12, 4) not null,
    currency text not null,
    status text not null check (status in ('win', 'lose', 'cancelled')),
    played_at timestamptz not null default now()
);

alter table public.profiles enable row level security;
alter table public.game_rounds enable row level security;

create policy "players can read their own profile" on public.profiles
    for select using (auth.uid() = id);
create policy "players can update their own profile" on public.profiles
    for update using (auth.uid() = id) with check (auth.uid() = id);
create policy "players can read their own rounds" on public.game_rounds
    for select using (auth.uid() = user_id);

-- Inserts are deliberately server-only through Laravel's service-role client.
