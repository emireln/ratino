<template>
    <div class="session-stats">
        <div class="stat-cards">
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.net') }}</div>
                <div class="stat-value" :class="stats.profit >= 0 ? 'text-success' : 'text-danger'">
                    <unit :to="'credits'" :value="stats.profit"></unit>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.wagered') }}</div>
                <div class="stat-value"><unit :to="'credits'" :value="stats.wager"></unit></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.wins') }}</div>
                <div class="stat-value text-success">{{ stats.wins }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.losses') }}</div>
                <div class="stat-value text-danger">{{ stats.losses }}</div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.biggest_win') }}</div>
                <div class="stat-value text-success"><unit :to="'credits'" :value="stats.biggestWin"></unit></div>
            </div>
            <div class="stat-card">
                <div class="stat-label">{{ $t('session.best_multiplier') }}</div>
                <div class="stat-value">{{ stats.bestMultiplier.toFixed(2) }}x</div>
            </div>
        </div>

        <div class="session-winbar">
            <div class="session-winbar-fill" :style="{ width: winRate + '%' }"></div>
        </div>
        <div class="session-winbar-label">{{ $t('session.win_rate', { rate: winRate.toFixed(1) }) }}</div>

        <div class="session-chart" ref="chart" v-if="stats.points.length > 1"></div>
        <div class="session-empty" v-else>
            <i class="fas fa-chart-line"></i>
            <div>{{ $t('session.empty') }}</div>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../stores/app';
import ApexCharts from 'apexcharts';

export default {
    data() {
        return { chart: null };
    },
    computed: {
        stats() { return useAppStore().sessionStats; },
        winRate() {
            const total = this.stats.wins + this.stats.losses;
            return total === 0 ? 0 : this.stats.wins / total * 100;
        }
    },
    watch: {
        'stats.points.length'() { this.renderChart(); }
    },
    methods: {
        renderChart() {
            if (!this.$refs.chart) return;
            if (this.chart) this.chart.destroy();
            const points = this.stats.points;
            if (points.length < 2) return;

            this.chart = new ApexCharts(this.$refs.chart, {
                chart: {
                    type: 'area',
                    height: 230,
                    toolbar: { show: false },
                    background: 'transparent'
                },
                series: [{
                    name: this.$t('session.net'),
                    data: points.map((p) => ({ x: p.x, y: p.y }))
                }],
                stroke: { curve: 'smooth', width: 2, colors: ['#e51f49'] },
                fill: {
                    type: 'gradient',
                    gradient: { shadeIntensity: 1, opacityFrom: 0.35, opacityTo: 0.02 }
                },
                colors: ['#e51f49'],
                dataLabels: { enabled: false },
                xaxis: { labels: { show: false } },
                yaxis: { labels: { style: { colors: ['#8a8f9c'] } } },
                grid: { borderColor: 'rgba(255,255,255,0.06)', strokeDashArray: 4 },
                tooltip: { theme: 'dark', y: { formatter: (v) => v.toFixed(2) } }
            });
            this.chart.render();
        }
    },
    mounted() {
        this.renderChart();
    },
    beforeUnmount() {
        if (this.chart) this.chart.destroy();
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.stat-cards {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 10px;

    .stat-card {
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 4px;
        padding: 12px 15px;
        text-align: center;

        .stat-label {
            font-size: .75em;
            text-transform: uppercase;
            letter-spacing: .08em;
            opacity: .6;
            margin-bottom: 6px;
        }

        .stat-value { font-size: 1.15em; font-weight: 600; }
    }
}

.session-winbar {
    height: 8px;
    border-radius: 4px;
    margin-top: 18px;
    overflow: hidden;
    background: rgba(255, 255, 255, .06);

    .session-winbar-fill {
        height: 100%;
        border-radius: 4px;
        background: #e51f49;
        transition: width .4s ease;
    }
}

.session-winbar-label {
    font-size: .8em;
    opacity: .6;
    margin-top: 5px;
    text-align: right;
}

.session-chart {
    margin-top: 15px;
    border-radius: 4px;
    padding: 10px;
    min-height: 230px;
    background: rgba(255, 255, 255, .02);
}

.session-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 200px;
    opacity: .4;

    i { font-size: 2.5em; margin-bottom: 12px; }
}

@media(max-width: 700px) {
    .stat-cards { grid-template-columns: repeat(2, 1fr); }
}
</style>
