<template>
    <div class="live-feed">
        <div class="live-feed-header">
            <div class="feed-title-left">
                <i class="fas fa-satellite-dish"></i>
                <span class="title-text">Live Bets Feed</span>
                <span class="live-pulse-badge"><i class="fas fa-circle"></i> REALTIME</span>
            </div>

            <!-- Feed Filter Tabs -->
            <div class="feed-filter-tabs">
                <button :class="['btn-feed-tab', filter === 'all' ? 'active' : '']" @click="filter = 'all'">
                    All Bets
                </button>
                <button :class="['btn-feed-tab', filter === 'high_rollers' ? 'active' : '']" @click="filter = 'high_rollers'">
                    <i class="fas fa-crown"></i> High Rollers
                </button>
                <button :class="['btn-feed-tab', filter === 'lucky' ? 'active' : '']" @click="filter = 'lucky'">
                    <i class="fas fa-bolt"></i> Lucky 10x+
                </button>
                <button :class="['btn-feed-tab', filter === 'friends' ? 'active' : '']" @click="filter = 'friends'">
                    <i class="fas fa-user-friends"></i> Friends
                </button>
            </div>
        </div>

        <div class="live-feed-rows">
            <div class="live-feed-row" v-for="(bet, i) in filteredBets" :key="bet._id || i">
                <div class="live-feed-game" :title="bet.game">
                    <icon :icon="gameIcon(bet.game)"></icon>
                </div>
                <div class="live-feed-user">
                    <span class="live-feed-name">
                        {{ bet.user ? bet.user.name : 'Guest' }}
                        <span v-if="isFriend(bet.user)" class="badge-friend"><i class="fas fa-user-check"></i> Friend</span>
                    </span>
                    <span class="live-feed-time">{{ timeAgo(bet.created_at) }}</span>
                </div>
                <div class="live-feed-wager">
                    <unit :to="'credits'" :value="bet.wager"></unit>
                </div>
                <div class="live-feed-multiplier" :class="bet.status === 'win' ? 'text-success' : 'text-danger'">
                    {{ (bet.multiplier ?? 0).toFixed(2) }}x
                </div>
                <div class="live-feed-quick-react">
                    <button class="btn-mini-react" @click="quickReact(bet, '🔥')" title="Drop 🔥 reaction">🔥</button>
                    <button class="btn-mini-react" @click="quickReact(bet, '👏')" title="Drop 👏 reaction">👏</button>
                </div>
            </div>

            <div class="live-feed-empty" v-if="!filteredBets.length">
                <i class="fas fa-satellite-dish"></i>
                <div>No bets found in this filter yet. Action updates in real time!</div>
            </div>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../stores/app';
import { whisper } from '../lib/whisper';
import bus from '../lib/bus';

export default {
    data() {
        return {
            bets: [],
            filter: 'all',
            friends: []
        };
    },
    computed: {
        store() {
            return useAppStore();
        },
        games() {
            return this.store.games || [];
        },
        filteredBets() {
            if (this.filter === 'high_rollers') {
                return this.bets.filter((b) => (b.wager >= 1000) || (b.profit >= 5000));
            }
            if (this.filter === 'lucky') {
                return this.bets.filter((b) => (b.multiplier >= 10));
            }
            if (this.filter === 'friends') {
                const friendNames = this.friends.map((f) => f.name.toLowerCase());
                return this.bets.filter((b) => b.user && friendNames.includes(b.user.name.toLowerCase()));
            }
            return this.bets;
        }
    },
    methods: {
        fetch() {
            whisper('LiveFeed').then((bets) => this.bets = bets || []).catch(() => {});
            whisper('Friend', { action: 'list' }).then((res) => {
                this.friends = res.friends || [];
            }).catch(() => {});
        },
        isFriend(user) {
            if (!user) return false;
            return this.friends.some((f) => f.name.toLowerCase() === user.name.toLowerCase());
        },
        gameIcon(gameId) {
            const game = this.games.find((g) => g.id === gameId);
            return game ? game.icon : 'fas fa-dice';
        },
        timeAgo(iso) {
            if (!iso) return 'just now';
            const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
            if (seconds < 60) return seconds + 's ago';
            return Math.floor(seconds / 60) + 'm ago';
        },
        onLiveBet(e) {
            this.bets.unshift(e);
            if (this.bets.length > 25) this.bets.pop();
        },
        quickReact(bet, emoji) {
            whisper('Friend', { action: 'react', emoji, channel: 'live' }).catch(() => {});
            this.$toast.success(`Dropped ${emoji} on ${bet.user ? bet.user.name : 'bet'}!`);
        }
    },
    mounted() {
        this.fetch();
        bus.on('event:liveBet', this.onLiveBet);
    },
    beforeUnmount() {
        bus.off('event:liveBet', this.onLiveBet);
    }
};
</script>

<style lang="scss">
@import '../sass/variables';

.live-feed {
    margin: 35px auto 10px;
    max-width: 1200px;
    width: 100%;

    .live-feed-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;

        .feed-title-left {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            font-size: .95em;
            color: #fff;

            i { color: #e51f49; }

            .live-pulse-badge {
                font-size: .72em;
                color: #2fd672;
                background: rgba(47, 214, 114, 0.12);
                padding: 2px 8px;
                border-radius: 12px;
                border: 1px solid rgba(47, 214, 114, 0.3);
                font-weight: 800;
                display: flex;
                align-items: center;
                gap: 5px;

                i {
                    color: #2fd672;
                    font-size: .55em;
                    animation: feed-pulse 1.5s infinite;
                }
            }
        }

        .feed-filter-tabs {
            display: flex;
            gap: 6px;
            background: rgba(0, 0, 0, 0.3);
            padding: 3px;
            border-radius: 8px;

            .btn-feed-tab {
                background: transparent;
                border: none;
                color: rgba(255, 255, 255, 0.6);
                padding: 5px 12px;
                border-radius: 6px;
                font-size: 0.8em;
                font-weight: 700;
                cursor: pointer;
                transition: all 0.2s ease;
                display: flex;
                align-items: center;
                gap: 5px;

                i { font-size: 0.9em; }

                &:hover {
                    color: #fff;
                    background: rgba(255, 255, 255, 0.06);
                }

                &.active {
                    background: #e51f49;
                    color: #fff;
                }
            }
        }
    }

    .live-feed-rows {
        background: #181920;
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 8px 24px rgba(0, 0, 0, 0.35);

        .live-feed-row {
            display: grid;
            grid-template-columns: 36px 1fr 130px 90px 70px;
            align-items: center;
            gap: 12px;
            padding: 10px 16px;
            font-size: .86em;
            border-bottom: 1px solid rgba(255, 255, 255, .05);
            transition: background 0.15s ease;

            &:hover {
                background: rgba(255, 255, 255, 0.03);
            }

            &:last-child { border-bottom: none; }

            .live-feed-game {
                width: 34px;
                height: 34px;
                border-radius: 8px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, .06);
                color: #e51f49;
                font-size: 1.1em;
            }

            .live-feed-name {
                font-weight: 700;
                color: #fff;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
                display: flex;
                align-items: center;
                gap: 6px;

                .badge-friend {
                    font-size: 0.68em;
                    color: #2fd672;
                    background: rgba(47, 214, 114, 0.15);
                    padding: 1px 6px;
                    border-radius: 4px;
                    font-weight: 700;
                }
            }

            .live-feed-time {
                font-size: .72em;
                color: rgba(255, 255, 255, 0.45);
                display: block;
                margin-top: 1px;
            }

            .live-feed-wager {
                text-align: right;
                font-weight: 600;
            }

            .live-feed-multiplier {
                text-align: right;
                font-weight: 800;
                font-size: 1.05em;
            }

            .live-feed-quick-react {
                display: flex;
                justify-content: flex-end;
                gap: 4px;

                .btn-mini-react {
                    background: rgba(255, 255, 255, 0.06);
                    border: 1px solid rgba(255, 255, 255, 0.1);
                    border-radius: 50%;
                    width: 26px;
                    height: 26px;
                    font-size: 0.85em;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    cursor: pointer;
                    transition: transform 0.15s ease, background 0.15s ease;

                    &:hover {
                        transform: scale(1.25);
                        background: rgba(229, 31, 73, 0.25);
                    }
                }
            }
        }

        .live-feed-empty {
            padding: 45px 20px;
            text-align: center;
            color: rgba(255, 255, 255, 0.45);
            font-size: 0.9em;

            i { display: block; font-size: 2em; margin-bottom: 10px; opacity: 0.5; }
        }
    }
}

@media(max-width: 600px) {
    .live-feed-rows .live-feed-row {
        grid-template-columns: 32px 1fr 90px 70px;
        .live-feed-quick-react { display: none; }
    }
}

@keyframes feed-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .25; }
}
</style>
