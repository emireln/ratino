<template>
    <div>
        <div class="lb-tabs">
            <div :class="['lb-tab', orderBy === 'wager' ? 'active' : '']" @click="orderBy = 'wager'">{{ $t('leaderboard.by_wager') }}</div>
            <div :class="['lb-tab', orderBy === 'multiplier' ? 'active' : '']" @click="orderBy = 'multiplier'">{{ $t('leaderboard.by_multiplier') }}</div>
        </div>
        <div class="lb-tabs lb-tabs-period">
            <div :class="['lb-tab', period === 'daily' ? 'active' : '']" @click="period = 'daily'">{{ $t('leaderboard.daily') }}</div>
            <div :class="['lb-tab', period === 'weekly' ? 'active' : '']" @click="period = 'weekly'">{{ $t('leaderboard.weekly') }}</div>
            <div :class="['lb-tab', period === 'alltime' ? 'active' : '']" @click="period = 'alltime'">{{ $t('leaderboard.alltime') }}</div>
        </div>

        <loader v-if="!entries"></loader>

        <div class="lb-table-wrap" v-else>
        <table class="lb-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>{{ $t('general.bets.player') }}</th>
                    <th class="text-right">{{ $t('leaderboard.' + (orderBy === 'wager' ? 'wager' : 'multiplier')) }}</th>
                    <th class="text-right" v-if="orderBy === 'wager'">{{ $t('general.profile.profit') }}</th>
                    <th class="text-right" v-else>{{ $t('leaderboard.game') }}</th>
                </tr>
            </thead>
            <tbody>
                <tr v-for="(entry, i) in entries" :key="i" :class="i === 0 ? 'first' : (i === 1 ? 'second' : (i === 2 ? 'third' : ''))">
                    <td>{{ i + 1 }}</td>
                    <td>
                        <div class="lb-player">
                            <img :src="entry.user.avatar" alt>
                            <span class="lb-name">{{ entry.user.name }}</span>
                        </div>
                    </td>
                    <td class="text-right">{{ orderBy === 'wager' ? fmt(entry.wager) : fmt(entry.multiplier) + 'x' }}</td>
                    <td class="text-right" v-if="orderBy === 'wager'" :class="entry.profit >= 0 ? 'text-success' : 'text-danger'">{{ fmt(entry.profit) }}</td>
                    <td class="text-right" v-else>{{ entry.game }}</td>
                </tr>
            </tbody>
        </table>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';

export default {
    data() {
        return {
            period: 'alltime',
            orderBy: 'wager',
            entries: null
        };
    },
    watch: {
        period() { this.load(); },
        orderBy() { this.load(); }
    },
    methods: {
        load() {
            this.entries = null;
            whisper('Leaderboard', { period: this.period, orderBy: this.orderBy })
                .then((entries) => this.entries = entries);
        },
        fmt(value) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 2 });
        }
    },
    mounted() {
        this.load();
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.lb-tabs {
    display: flex;
    margin-bottom: 10px;

    &.lb-tabs-period { margin-bottom: 18px; }

    .lb-tab {
        flex: 1;
        text-align: center;
        padding: 9px;
        cursor: pointer;
        font-weight: 600;
        text-transform: uppercase;
        font-size: .78em;
        letter-spacing: .05em;
        background: rgba(255, 255, 255, .06);
        border-radius: 4px;
        margin-right: 6px;

        &:last-child { margin-right: 0; }

        &.active {
            background: #e51f49;
            color: #fff;
        }
    }
}

.lb-table-wrap {
    overflow-x: auto;
    margin: 0 -4px;
    padding: 0 4px;
}

.lb-table {
    width: 100%;
    min-width: 320px;
    font-size: .9em;
    border-collapse: collapse;

    th, td {
        padding: 9px 10px;
        border-bottom: 1px solid rgba(255, 255, 255, .05);
    }

    th { opacity: .6; font-size: .8em; text-transform: uppercase; }

    .lb-player {
        display: flex;
        align-items: center;
        gap: 8px;

        img {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            flex-shrink: 0;
        }

        .lb-name {
            max-width: 130px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
    }

    .first td { color: #ffdf7c; }
    .second td { color: #bfdffb; }
    .third td { color: #d0898f; }
}
</style>
