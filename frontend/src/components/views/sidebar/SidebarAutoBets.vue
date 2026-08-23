<template>
    <div class="sidebar-auto-bets">
        <div class="ab-row" v-if="gameInstance.game && gameInstance.game.autoBetSettings.supported">
            <label>
                <input type="checkbox" v-model="autoBet"> Auto-bet
            </label>
        </div>
        <div v-if="autoBet" class="ab-settings">
            <div class="ab-field">
                <span>Games</span>
                <input type="number" v-model.number="games" min="0">
            </div>
            <div class="ab-field">
                <span>Stop on win</span>
                <input type="checkbox" v-model="stopOnWin">
            </div>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../../stores/app';

export default {
    data() {
        return { autoBet: false, games: 0, stopOnWin: false };
    },
    computed: {
        gameInstance() { return useAppStore().gameInstance; }
    },
    watch: {
        autoBet(v) {
            this.updateGameInstance((i) => {
                i.game.autoBetSettings.state = v;
                i.game.autoBetSettings.games = this.games;
                i.game.autoBetSettings.stopOnWin = this.stopOnWin;
            });
        },
        games(v) {
            this.updateGameInstance((i) => i.game.autoBetSettings.games = v);
        },
        stopOnWin(v) {
            this.updateGameInstance((i) => i.game.autoBetSettings.stopOnWin = v);
        }
    }
};
</script>

<style lang="scss" scoped>
.sidebar-auto-bets {
    font-size: .9em;

    .ab-row { margin-top: 8px; }

    .ab-settings {
        display: flex;
        flex-direction: column;
        gap: 8px;
        margin-top: 8px;
    }

    .ab-field {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;

        input[type="number"] { width: 90px; }
    }
}
</style>
