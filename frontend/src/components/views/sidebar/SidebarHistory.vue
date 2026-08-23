<template>
    <div class="sidebar-history">
        <div class="history-scroll">
            <div v-for="(entry, i) in entries" :key="i" class="history-entry">
                <span v-html="entry.html || entry.text"></span>
            </div>
        </div>
    </div>
</template>

<script>
import bus from '../../../lib/bus';

const EVENTS = [
    'limbo:history:addEntry',
    'dice:history:addEntry',
    'keno:history:addEntry',
    'diamonds:history:addEntry',
    'mines:history:addEntry',
    'tower:history:addEntry',
    'wheel:history:addEntry',
    'coinflip:history:addEntry',
    'hilo:history:addEntry',
    'stairs:history:addEntry',
    'videopoker:history:addEntry',
    'slots:history:addEntry',
    'roulette:history:addEntry',
    'blackjack:history:addEntry',
    'baccarat:history:addEntry'
];

export default {
    data() {
        return { entries: [] };
    },
    mounted() {
        this.unsubs = EVENTS.map((event) => bus.on(event, (e) => {
            this.entries.unshift(e);
            if (this.entries.length > 30) this.entries.pop();
        }));
    },
    beforeUnmount() {
        if (Array.isArray(this.unsubs)) {
            this.unsubs.forEach((unsub) => {
                if (typeof unsub === 'function') unsub();
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.sidebar-history {
    margin-top: 8px;

    .history-scroll {
        max-height: 220px;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 4px;
    }

    .history-entry {
        padding: 6px 10px;
        border-radius: 4px;
        font-size: .8em;
        background: rgba(255, 255, 255, .04);
        cursor: default;

        .color {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            margin-right: 6px;
        }
    }
}
</style>
