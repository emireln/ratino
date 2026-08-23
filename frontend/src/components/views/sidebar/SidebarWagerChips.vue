<template>
    <div class="wager-chips">
        <div v-for="(chip, i) in chips" :key="i" class="wager-chip" @click="select(chip)">{{ chip }}</div>
    </div>
</template>

<script>
import bus from '../../../lib/bus';

export default {
    data() {
        return { chips: [10, 50, 100, 500, 1000, 5000] };
    },
    methods: {
        select(chip) {
            this.updateGameInstance((i) => i.bet = chip);
            bus.emit('sidebar:chipSelect', { value: chip, displayValue: chip });
        }
    }
};
</script>

<style lang="scss" scoped>
.wager-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;

    .wager-chip {
        padding: 7px 10px;
        border-radius: 4px;
        cursor: pointer;
        font-size: .85em;
        font-weight: 600;
        background: rgba(255, 255, 255, .06);

        &:hover { background: rgba(255, 255, 255, .12); }
    }
}
</style>
