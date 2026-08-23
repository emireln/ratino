<template>
    <div>
        <div class="vip-hero">
            <div class="vip-hero-icon" :style="{ color: color(status ? status.tier : 0) }">
                <i :class="icon(status ? status.tier : 0)"></i>
            </div>
            <div class="vip-hero-name" v-if="status">
                <span class="tier-name">{{ $t('vip.tier.' + status.tierName) }}</span>
                <span class="tier-sub">{{ status.rakebackRate }}% {{ $t('vip.rakeback_rate') }}</span>
            </div>
            <div class="vip-hero-claim" v-if="status && status.rakeback > 0">
                <button class="btn btn-primary btn-block" @click="claim">
                    {{ $t('vip.claim_rakeback') }} — <unit :to="'credits'" :value="status.rakeback"></unit>
                </button>
            </div>
            <div class="vip-hero-claim" v-else-if="status">
                <button class="btn btn-secondary btn-block" disabled>{{ $t('vip.no_rakeback') }}</button>
            </div>
        </div>

        <loader v-if="!status"></loader>

        <template v-if="status">
            <div class="vip-progress-block">
                <div class="vip-progress">
                    <div class="vip-progress-bar" :style="{ width: status.progress + '%', background: color(status.tier) }"></div>
                </div>
                <div class="vip-progress-info">
                    <span>{{ $t('vip.wagered') }}: <b>{{ format(status.wager) }}</b> Chips</span>
                    <span v-if="status.nextTier">{{ $t('vip.next_tier') }}: <b>{{ $t('vip.tier.' + status.nextTier) }}</b> — {{ format(status.nextWager) }}</span>
                    <span v-else>{{ $t('vip.max_tier') }}</span>
                </div>
            </div>

            <div class="vip-tiers">
                <div v-for="(tier, i) in status.milestones" :key="tier.name"
                    :class="['vip-tier', i <= status.tier ? 'reached' : '', i === status.tier ? 'current' : '']"
                    :style="{ borderColor: i <= status.tier ? color(i) : 'rgba(255,255,255,.08)' }">
                    <div class="vip-tier-icon" :style="{ color: color(i) }"><i :class="icon(i)"></i></div>
                    <div class="vip-tier-name">{{ $t('vip.tier.' + tier.name) }}</div>
                    <div class="vip-tier-reward">{{ format(tier.reward) }} Chips</div>
                    <div class="vip-tier-status">
                        <i v-if="tier.claimed" class="fas fa-check-circle" style="color: #2fd672"></i>
                        <span v-else-if="tier.reached" style="color: #ffd23f">{{ $t('vip.milestone_reached') }}</span>
                        <span v-else>{{ $t('vip.locked') }}</span>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';
import { useAppStore } from '../../stores/app';

const TIER_COLORS = ['#a0765a', '#b9c0c9', '#e8c53c', '#9bd1e8', '#58d1f2', '#b06bf0', '#ffd23f'];
const TIER_ICONS = ['fas fa-medal', 'fas fa-medal', 'fas fa-medal', 'fas fa-gem', 'fas fa-gem', 'fas fa-crown', 'fas fa-crown'];

export default {
    data() {
        return { status: null };
    },
    methods: {
        claim() {
            whisper('Vip', { action: 'claim' }).then((response) => {
                this.status = response.status;
                this.$toast.success(this.$t('vip.claimed', { amount: response.claimed }));
                useAppStore().updateData();
            });
        },
        color(i) { return TIER_COLORS[i]; },
        icon(i) { return TIER_ICONS[i]; },
        format(value) {
            return Number(value).toLocaleString(undefined, { maximumFractionDigits: 0 });
        }
    },
    mounted() {
        whisper('Vip', { action: 'status' }).then((status) => this.status = status).catch(() => {});
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.vip-hero {
    display: flex;
    flex-direction: column;
    align-items: center;
    text-align: center;
    padding: 10px 0 20px;

    .vip-hero-icon { font-size: 3em; margin-bottom: 10px; }

    .vip-hero-name {
        display: flex;
        flex-direction: column;
        margin-bottom: 15px;

        .tier-name { font-size: 1.4em; font-weight: 700; }
        .tier-sub { font-size: .85em; opacity: .6; }
    }

    .vip-hero-claim { width: 260px; }
}

.vip-progress-block { margin-bottom: 20px; }

.vip-progress {
    height: 12px;
    border-radius: 4px;
    background: rgba(255, 255, 255, .07);
    overflow: hidden;

    .vip-progress-bar {
        height: 12px;
        transition: width .5s ease;
    }
}

.vip-progress-info {
    display: flex;
    justify-content: space-between;
    font-size: .8em;
    margin-top: 6px;
    opacity: .8;
}

.vip-tiers {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 8px;

    .vip-tier {
        border: 2px solid rgba(255, 255, 255, .08);
        border-radius: 6px;
        padding: 12px 8px;
        text-align: center;
        display: flex;
        flex-direction: column;
        align-items: center;
        opacity: .45;
        transition: opacity .3s ease, transform .3s ease;

        &.reached { opacity: .85; }
        &.current { opacity: 1; transform: scale(1.05); }

        .vip-tier-icon { font-size: 1.4em; margin-bottom: 6px; }
        .vip-tier-name {
            font-size: .72em;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: .05em;
            margin-bottom: 4px;
        }
        .vip-tier-reward { font-size: .7em; opacity: .75; margin-bottom: 4px; }
        .vip-tier-status { font-size: .66em; }
    }
}

@media(max-width: 620px) {
    .vip-tiers { grid-template-columns: repeat(2, 1fr); }
}
</style>
