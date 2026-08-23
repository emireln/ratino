<template>
    <div class="live-feed">
        <div class="live-feed-header">
            <span><i class="fas fa-satellite-dish"></i> {{ $t('feed.title') }}</span>
            <span class="live-feed-live"><i class="fas fa-circle"></i> {{ $t('feed.live') }}</span>
        </div>
        <div class="live-feed-rows">
            <div class="live-feed-row" v-for="(bet, i) in bets" :key="i">
                <div class="live-feed-game"><icon :icon="gameIcon(bet.game)"></icon></div>
                <div class="live-feed-user">
                    <span class="live-feed-name">{{ bet.user ? bet.user.name : 'Guest' }}</span>
                    <span class="live-feed-time">{{ timeAgo(bet.created_at) }}</span>
                </div>
                <div class="live-feed-wager"><unit :to="'credits'" :value="bet.wager"></unit></div>
                <div class="live-feed-multiplier" :class="bet.status === 'win' ? 'text-success' : 'text-danger'">
                    {{ (bet.multiplier ?? 0).toFixed(2) }}x
                </div>
            </div>
            <div class="live-feed-empty" v-if="!bets.length">
                <i class="fas fa-satellite-dish"></i>
                {{ $t('feed.empty') }}
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
        return { bets: [] };
    },
    computed: {
        games() { return useAppStore().games; }
    },
    methods: {
        fetch() {
            whisper('LiveFeed').then((bets) => this.bets = bets).catch(() => {});
        },
        gameIcon(gameId) {
            const game = this.games.find((g) => g.id === gameId);
            return game ? game.icon : 'fas fa-dice';
        },
        timeAgo(iso) {
            if (!iso) return '';
            const seconds = Math.max(0, Math.floor((Date.now() - new Date(iso).getTime()) / 1000));
            if (seconds < 60) return seconds + 's';
            return Math.floor(seconds / 60) + 'm';
        },
        onLiveBet(e) {
            this.bets.unshift(e);
            if (this.bets.length > 15) this.bets.pop();
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

    .live-feed-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: .08em;
        font-size: .95em;
        margin-bottom: 15px;

        i { margin-right: 8px; }

        .live-feed-live {
            font-size: .75em;
            opacity: .75;

            i { color: #2fd672; font-size: .6em; animation: feed-pulse 1.5s infinite; }
        }
    }

    .live-feed-rows {
        @include themed() {
            background: rgba(t('sidebar'), .7);
            border: 1px solid t('border');
        }
        border-radius: 8px;
        overflow: hidden;

        .live-feed-row {
            display: grid;
            grid-template-columns: 34px 1fr 130px 90px;
            align-items: center;
            gap: 12px;
            padding: 9px 16px;
            font-size: .85em;
            border-bottom: 1px solid rgba(255, 255, 255, .06);

            &:last-child { border-bottom: none; }

            .live-feed-game {
                width: 34px;
                height: 34px;
                border-radius: 6px;
                display: flex;
                align-items: center;
                justify-content: center;
                background: rgba(255, 255, 255, .06);
            }

            .live-feed-name {
                font-weight: 600;
                overflow: hidden;
                text-overflow: ellipsis;
                white-space: nowrap;
            }

            .live-feed-time { font-size: .72em; opacity: .5; display: block; }

            .live-feed-wager, .live-feed-multiplier { text-align: right; }

            .live-feed-multiplier { font-weight: 700; }
        }

        .live-feed-empty {
            padding: 35px;
            text-align: center;
            opacity: .4;

            i { display: block; font-size: 1.8em; margin-bottom: 10px; }
        }
    }
}

@keyframes feed-pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: .3; }
}
</style>
