<template>
    <div class="sidebar-profit" v-if="gameInstance.bet">
        <span class="sp-label">Current profit</span>
        <span class="sp-value" :class="profit >= 0 ? 'text-success' : 'text-danger'">
            <unit :to="currency" :value="profit"></unit>
        </span>
    </div>
</template>

<script>
import { useAppStore } from '../../../stores/app';

export default {
    computed: {
        gameInstance() { return useAppStore().gameInstance; },
        currency() { return useAppStore().currency; },
        profit() {
            return Number(this.gameInstance.game?.currentProfit ?? 0) - Number(this.gameInstance.bet ?? 0);
        }
    }
};
</script>

<style lang="scss" scoped>
.sidebar-profit {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 8px 10px;
    border-radius: 4px;
    background: rgba(255, 255, 255, .04);
    font-size: .9em;

    .sp-value { font-weight: 700; }
}
</style>
