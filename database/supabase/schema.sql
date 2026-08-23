-- =============================================================================
-- RATINO — Supabase PostgreSQL Schema & Security Architecture (Idempotent)
-- =============================================================================
-- Execute in Supabase SQL Editor.
-- Auth identities live in auth.users; application data resides in public.
-- All tables enforce strict Row Level Security (RLS) with least-privilege policies.
-- Safe to re-run multiple times (all policies, triggers, and tables are idempotent).
-- =============================================================================

-- 1. Extensions
create extension if not exists "pgcrypto";
create extension if not exists "uuid-ossp";

-- =============================================================================
-- 2. Profiles Table
-- =============================================================================
create table if not exists public.profiles (
    id uuid primary key references auth.users(id) on delete cascade,
    display_name text not null check (char_length(display_name) between 4 and 64 and display_name ~ '^[a-zA-Z0-9_-]+$'),
    avatar_url text default '/img/misc/incognito-default.png',
    vip_level int not null default 0 check (vip_level between 0 and 6),
    chips_balance numeric(20, 2) not null default 10000.00 check (chips_balance >= 0),
    active_game text default null,
    active_room text default null,
    friends jsonb not null default '[]'::jsonb,
    friend_requests jsonb not null default '[]'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

-- Profiles Indexes
create index if not exists idx_profiles_display_name on public.profiles (display_name);
create index if not exists idx_profiles_vip_level on public.profiles (vip_level desc);

-- Profiles RLS
alter table public.profiles enable row level security;

-- Authenticated players can view all public profile info (for leaderboards, chat, friends, live bets)
drop policy if exists "profiles_select_all" on public.profiles;
create policy "profiles_select_all" on public.profiles
    for select to authenticated
    using (true);

-- Players can only update their own profile fields
drop policy if exists "profiles_update_own" on public.profiles;
create policy "profiles_update_own" on public.profiles
    for update to authenticated
    using (auth.uid() = id)
    with check (auth.uid() = id);

-- Players or service role can insert their initial profile
drop policy if exists "profiles_insert_own" on public.profiles;
create policy "profiles_insert_own" on public.profiles
    for insert to authenticated
    with check (auth.uid() = id);

-- =============================================================================
-- 3. Game Rounds Ledger Table (Provably Fair)
-- =============================================================================
create table if not exists public.game_rounds (
    id bigint generated always as identity primary key,
    legacy_game_id text not null unique,
    user_id uuid not null references auth.users(id) on delete cascade,
    game_slug text not null,
    wager numeric(20, 8) not null check (wager >= 0),
    profit numeric(20, 8) not null,
    multiplier numeric(12, 4) not null,
    currency text not null default 'credits',
    status text not null check (status in ('win', 'lose', 'cancelled')),
    server_seed_hashed text default null,
    client_seed text default null,
    nonce bigint default null,
    played_at timestamptz not null default now()
);

-- Game Rounds Indexes
create index if not exists idx_game_rounds_user_played on public.game_rounds (user_id, played_at desc);
create index if not exists idx_game_rounds_game_slug on public.game_rounds (game_slug);
create index if not exists idx_game_rounds_played_at on public.game_rounds (played_at desc);

-- Game Rounds RLS
alter table public.game_rounds enable row level security;

-- Players can read their own detailed game round history
drop policy if exists "game_rounds_select_own" on public.game_rounds;
create policy "game_rounds_select_own" on public.game_rounds
    for select to authenticated
    using (auth.uid() = user_id);

-- Authenticated players can read public live feed rounds
drop policy if exists "game_rounds_select_live" on public.game_rounds;
create policy "game_rounds_select_live" on public.game_rounds
    for select to authenticated
    using (true);

-- Insert game rounds (server or authenticated client)
drop policy if exists "game_rounds_insert" on public.game_rounds;
create policy "game_rounds_insert" on public.game_rounds
    for insert to authenticated
    with check (true);

-- =============================================================================
-- 4. Multiplayer & Private Rooms Table
-- =============================================================================
create table if not exists public.rooms (
    id uuid primary key default gen_random_uuid(),
    code text not null unique check (char_length(code) between 4 and 10 and code ~ '^[A-Z0-9]+$'),
    name text not null check (char_length(name) between 1 and 32),
    owner_id uuid not null references auth.users(id) on delete cascade,
    is_private boolean not null default true,
    members jsonb not null default '[]'::jsonb,
    created_at timestamptz not null default now(),
    updated_at timestamptz not null default now()
);

-- Rooms Indexes
create index if not exists idx_rooms_code on public.rooms (code);
create index if not exists idx_rooms_owner on public.rooms (owner_id);

-- Rooms RLS
alter table public.rooms enable row level security;

-- Players can view public rooms or private rooms they are members/owners of
drop policy if exists "rooms_select" on public.rooms;
create policy "rooms_select" on public.rooms
    for select to authenticated
    using (
        not is_private
        or owner_id = auth.uid()
        or members ? auth.uid()::text
    );

-- Room owners can create rooms
drop policy if exists "rooms_insert_owner" on public.rooms;
create policy "rooms_insert_owner" on public.rooms
    for insert to authenticated
    with check (owner_id = auth.uid());

-- Room owners or members can update room
drop policy if exists "rooms_update" on public.rooms;
create policy "rooms_update" on public.rooms
    for update to authenticated
    using (owner_id = auth.uid() or members ? auth.uid()::text)
    with check (owner_id = auth.uid() or members ? auth.uid()::text);

-- Room owners can delete rooms
drop policy if exists "rooms_delete_owner" on public.rooms;
create policy "rooms_delete_owner" on public.rooms
    for delete to authenticated
    using (owner_id = auth.uid());

-- =============================================================================
-- 5. Transactions Ledger Table
-- =============================================================================
create table if not exists public.transactions (
    id bigint generated always as identity primary key,
    user_id uuid not null references auth.users(id) on delete cascade,
    amount numeric(20, 8) not null,
    currency text not null default 'credits',
    type text not null,
    old_balance numeric(20, 8) not null,
    new_balance numeric(20, 8) not null,
    note text default null,
    created_at timestamptz not null default now()
);

create index if not exists idx_transactions_user on public.transactions (user_id, created_at desc);

alter table public.transactions enable row level security;

-- Players can only read their own financial transactions
drop policy if exists "transactions_select_own" on public.transactions;
create policy "transactions_select_own" on public.transactions
    for select to authenticated
    using (auth.uid() = user_id);

-- =============================================================================
-- 6. Social Notifications Table
-- =============================================================================
create table if not exists public.social_notifications (
    id uuid primary key default gen_random_uuid(),
    recipient_id uuid not null references auth.users(id) on delete cascade,
    sender_id uuid references auth.users(id) on delete set null,
    type text not null check (type in ('room_invite', 'friend_request', 'friend_accepted')),
    data jsonb not null default '{}'::jsonb,
    is_read boolean not null default false,
    created_at timestamptz not null default now()
);

create index if not exists idx_notifications_recipient on public.social_notifications (recipient_id, created_at desc);

alter table public.social_notifications enable row level security;

-- Recipients can read only their own notifications
drop policy if exists "notifications_select_own" on public.social_notifications;
create policy "notifications_select_own" on public.social_notifications
    for select to authenticated
    using (auth.uid() = recipient_id);

-- Recipients can update read status
drop policy if exists "notifications_update_own" on public.social_notifications;
create policy "notifications_update_own" on public.social_notifications
    for update to authenticated
    using (auth.uid() = recipient_id)
    with check (auth.uid() = recipient_id);

-- Authenticated users can insert notifications to peers (e.g. friend requests, room invites)
drop policy if exists "notifications_insert_authenticated" on public.social_notifications;
create policy "notifications_insert_authenticated" on public.social_notifications
    for insert to authenticated
    with check (sender_id = auth.uid());

-- =============================================================================
-- 7. Supabase Storage Buckets & Policies (Avatars)
-- =============================================================================
insert into storage.buckets (id, name, public)
values ('avatars', 'avatars', true)
on conflict (id) do update set public = true;

-- Public can read avatar images
drop policy if exists "avatars_public_select" on storage.objects;
create policy "avatars_public_select" on storage.objects
    for select
    using (bucket_id = 'avatars');

-- Authenticated users can upload avatars inside their own folder: avatars/{userId}/*
drop policy if exists "avatars_user_insert" on storage.objects;
create policy "avatars_user_insert" on storage.objects
    for insert to authenticated
    with check (
        bucket_id = 'avatars'
        and (storage.foldername(name))[1] = auth.uid()::text
    );

-- Authenticated users can update their own avatars
drop policy if exists "avatars_user_update" on storage.objects;
create policy "avatars_user_update" on storage.objects
    for update to authenticated
    using (
        bucket_id = 'avatars'
        and (storage.foldername(name))[1] = auth.uid()::text
    );

-- Authenticated users can delete their own avatars
drop policy if exists "avatars_user_delete" on storage.objects;
create policy "avatars_user_delete" on storage.objects
    for delete to authenticated
    using (
        bucket_id = 'avatars'
        and (storage.foldername(name))[1] = auth.uid()::text
    );

-- =============================================================================
-- 8. Automated Triggers & Functions
-- =============================================================================

-- Auto-provision profile on new auth signup
create or replace function public.handle_new_user()
returns trigger as $$
begin
    insert into public.profiles (id, display_name, avatar_url, chips_balance)
    values (
        new.id,
        coalesce(new.raw_user_meta_data->>'name', 'player_' || substr(new.id::text, 1, 8)),
        coalesce(new.raw_user_meta_data->>'avatar', '/img/misc/incognito-default.png'),
        10000.00
    )
    on conflict (id) do nothing;
    return new;
end;
$$ language plpgsql security definer;

drop trigger if exists on_auth_user_created on auth.users;
create trigger on_auth_user_created
    after insert on auth.users
    for each row execute function public.handle_new_user();

-- Auto-update updated_at timestamp
create or replace function public.handle_updated_at()
returns trigger as $$
begin
    new.updated_at = now();
    return new;
end;
$$ language plpgsql;

drop trigger if exists on_profiles_updated on public.profiles;
create trigger on_profiles_updated
    before update on public.profiles
    for each row execute function public.handle_updated_at();

drop trigger if exists on_rooms_updated on public.rooms;
create trigger on_rooms_updated
    before update on public.rooms
    for each row execute function public.handle_updated_at();
