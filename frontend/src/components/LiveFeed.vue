<template>
    <div class="live-feed">
        <div class="live-feed-header">
            <div class="feed-title-left">
                <i class="fas fa-satellite-dish"></i>
                <span class="title-text">Live Bets</span>
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
    margin: 35px 0 15px;
    width: 100%;
    max-width: 100%;

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
            grid-template-columns: 36px 1fr 130px 90px;
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
            }

            .live-feed-wager {
                text-align: right;
                font-weight: 600;
                color: rgba(255, 255, 255, 0.85);
            }

            .live-feed-multiplier {
                text-align: right;
                font-weight: 800;
                font-size: 1.05em;
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
    }
}
</style>
