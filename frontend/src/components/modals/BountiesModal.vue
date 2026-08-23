<template>
    <div>
        <div class="bounty-tabs">
            <div :class="['bounty-tab', type === 'daily' ? 'active' : '']" @click="type = 'daily'">{{ $t('bounties.daily') }}</div>
            <div :class="['bounty-tab', type === 'weekly' ? 'active' : '']" @click="type = 'weekly'">{{ $t('bounties.weekly') }}</div>
        </div>

        <loader v-if="!progress"></loader>

        <div v-else class="bounty-list">
            <div v-for="bounty in progress[type].bounties" :key="bounty.id" :class="['bounty-card', bounty.completed ? 'completed' : '']">
                <div class="bounty-title-row">
                    <div class="bounty-title">
                        <i :class="bounty.claimed ? 'fas fa-check-circle' : 'far fa-circle'"></i>
                        <span>{{ bounty.title }}</span>
                    </div>
                    <div class="bounty-reward">{{ fmt(bounty.reward) }} Chips</div>
                </div>
                <div class="bounty-progress">
                    <div class="bounty-progress-bar" :class="bounty.completed ? 'done' : ''" :style="{ width: pct(bounty) + '%' }"></div>
                </div>
                <div class="bounty-meta">
                    <span v-if="bounty.claimed">{{ $t('bounties.claimed') }}</span>
                    <span v-else>{{ fmt(bounty.current) }} / {{ fmt(bounty.target) }} {{ $t('bounties.progress_unit') }}</span>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';

export default {
    data() {
        return {
            progress: null,
            type: 'daily'
        };
    },
    methods: {
        fmt(value) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
        },
        pct(bounty) {
            return Math.min(100, bounty.current / bounty.target * 100).toFixed(1);
        }
    },
    mounted() {
        whisper('Bounties').then((progress) => this.progress = progress).catch(() => {});
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.bounty-tabs {
    display: flex;
    margin-bottom: 18px;

    .bounty-tab {
        flex: 1;
        text-align: center;
        padding: 10px;
        cursor: pointer;
        font-weight: 600;
        text-transform: uppercase;
        font-size: .85em;
        letter-spacing: .06em;
        background: rgba(255, 255, 255, .06);
        border-radius: 4px;
        margin-right: 8px;

        &:last-child { margin-right: 0; }

        &.active {
            background: #e51f49;
            color: #fff;
        }
    }
}

.bounty-list {
    display: flex;
    flex-direction: column;
    gap: 12px;

    .bounty-card {
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .07);
        border-radius: 6px;
        padding: 14px 16px;

        &.completed { border-color: #2fd672; }

        .bounty-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;

            .bounty-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: .95em;

                i { color: #8a8f9c; }
                i.fa-check-circle { color: #2fd672; }
            }

            .bounty-reward {
                font-weight: 700;
                color: #e8c53c;
                white-space: nowrap;
                font-size: .9em;
            }
        }

        .bounty-progress {
            height: 7px;
            border-radius: 4px;
            background: rgba(255, 255, 255, .07);
            overflow: hidden;

            .bounty-progress-bar {
                height: 7px;
                background: #e51f49;
                transition: width .4s ease;

                &.done { background: #2fd672; }
            }
        }

        .bounty-meta {
            margin-top: 6px;
            font-size: .75em;
            opacity: .6;
            text-align: right;
        }
    }
}
</style>
