<template>
    <div class="wager-classic">
        <div class="wager-row">
            <span>1/2</span>
            <input type="number" v-model.number="bet" :min="1" step="1" @input="commit">
            <span>2x</span>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../../stores/app';

export default {
    data() {
        return { bet: 100 };
    },
    mounted() {
        if (this.gameInstance.bet) this.bet = this.gameInstance.bet;
    },
    computed: {
        gameInstance() { return useAppStore().gameInstance; }
    },
    methods: {
        commit() {
            this.updateGameInstance((i) => i.bet = this.bet);
        }
    }
};
</script>

<style lang="scss" scoped>
.wager-row {
    display: flex;
    align-items: center;
    gap: 10px;

    input { flex: 1; text-align: center; }
}
</style>
