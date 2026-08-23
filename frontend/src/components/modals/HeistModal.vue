<template>
    <div>
        <div class="heist-intro">
            <i class="fas fa-dice"></i>
            <span>{{ $t('heist.subtitle') }}</span>
        </div>

        <div class="heist-wheel-wrap">
            <div class="heist-pointer"><i class="fas fa-caret-down"></i></div>
            <div class="heist-wheel" :style="{ transform: `rotate(${rotation}deg)`, background: `conic-gradient(${segmentsCSS})` }">
                <div class="heist-hub"><i class="fas fa-coins"></i></div>
            </div>
        </div>

        <div class="heist-result" v-if="result">
            <span class="heist-result-label">{{ $t('heist.won') }}</span>
            <span class="heist-result-amount">{{ result.reward.toLocaleString() }} $CHEDDAR</span>
        </div>

        <div class="heist-actions">
            <button class="btn btn-primary btn-block" :disabled="spinning || !status || !status.canSpin" @click="spin">
                {{ spinning ? $t('heist.spinning') : (status && !status.canSpin ? $t('heist.next_spin', { time: fmt(status.secondsUntil) }) : $t('heist.spin')) }}
            </button>
        </div>

        <div class="heist-segments">
            <div v-for="(reward, i) in (status ? status.segments : [])" :key="i" class="heist-segment">
                <span class="dot" :style="{ background: color(i) }"></span>
                <span>{{ reward.toLocaleString() }}</span>
            </div>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';
import { useAppStore } from '../../stores/app';

const COLORS = ['#e51f49', '#e8c53c', '#58d1f2', '#b06bf0', '#2fd672', '#ff8f3f', '#3f7bff', '#ffd23f'];

export default {
    data() {
        return {
            status: null,
            spinning: false,
            rotation: 0,
            result: null,
            tick: null
        };
    },
    computed: {
        segmentsCSS() {
            const segs = this.status ? this.status.segments : [];
            if (!segs.length) return '#333';
            const angle = 360 / segs.length;
            const css = segs.map((r, i) => `${COLORS[i % COLORS.length]} ${(i * angle).toFixed(2)}deg ${((i + 1) * angle).toFixed(2)}deg`);
            return css.join(', ');
        }
    },
    methods: {
        fmt(seconds) {
            const h = Math.floor(seconds / 3600), m = Math.floor((seconds % 3600) / 60), s = seconds % 60;
            return `${h}h ${String(m).padStart(2, '0')}m ${String(s).padStart(2, '0')}s`;
        },
        spin() {
            if (this.spinning || !this.status || !this.status.canSpin) return;
            this.spinning = true;
            this.result = null;

            whisper('Heist', { action: 'spin' }).then((response) => {
                this.status = { ...this.status, canSpin: response.canSpin, secondsUntil: response.secondsUntil };

                const segmentAngle = 360 / this.status.segments.length;
                const target = 360 - ((response.index + 0.5) * segmentAngle);
                this.rotation += 5 * 360 + target - (this.rotation % 360) + 360;

                setTimeout(() => {
                    this.result = { index: response.index, reward: response.reward };
                    this.spinning = false;
                    this.$toast.success(`${this.$t('heist.won')} ${response.reward} $CHEDDAR`);
                    useAppStore().updateData();
                }, 4200);
            }).catch(() => this.spinning = false);
        },
        color(i) { return COLORS[i % COLORS.length]; }
    },
    mounted() {
        whisper('Heist', { action: 'status' }).then((status) => {
            this.status = status;
            this.tick = setInterval(() => {
                if (this.status && !this.status.canSpin) this.status.secondsUntil--;
            }, 1000);
        }).catch(() => {});
    },
    beforeUnmount() {
        clearInterval(this.tick);
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.heist-intro {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    opacity: .75;
    font-size: .95em;
    margin-bottom: 20px;

    i { color: #e8c53c; }
}

.heist-wheel-wrap {
    position: relative;
    width: 280px;
    height: 280px;
    margin: 0 auto 20px;

    .heist-pointer {
        position: absolute;
        top: -10px;
        left: 50%;
        transform: translateX(-50%);
        z-index: 5;
        color: #fff;
        font-size: 2em;
        text-shadow: 0 2px 6px rgba(0, 0, 0, .5);
    }

    .heist-wheel {
        width: 100%;
        height: 100%;
        border-radius: 50%;
        transition: transform 4s cubic-bezier(0.12, 0.75, 0.15, 1);
        box-shadow: 0 10px 40px rgba(0, 0, 0, .45), inset 0 0 0 6px rgba(0, 0, 0, .25);
        position: relative;

        .heist-hub {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 56px;
            height: 56px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3em;
            background: #21232a;
            box-shadow: 0 2px 10px rgba(0, 0, 0, .4);
        }
    }
}

.heist-result {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-bottom: 15px;

    .heist-result-label {
        font-size: .8em;
        text-transform: uppercase;
        letter-spacing: .1em;
        opacity: .6;
    }

    .heist-result-amount { font-size: 1.6em; font-weight: 700; color: #e8c53c; }
}

.heist-segments {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 6px;
    margin-top: 15px;

    .heist-segment {
        display: flex;
        align-items: center;
        gap: 7px;
        font-size: .78em;
        opacity: .85;

        .dot { width: 10px; height: 10px; border-radius: 50%; flex-shrink: 0; }
    }
}
</style>
