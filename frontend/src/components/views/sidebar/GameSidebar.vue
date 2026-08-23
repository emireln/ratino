<template>
    <div class="game-sidebar">
        <div class="game-sidebar-sticky">
            <div class="game-sidebar-tabs">
                <div :class="['game-sidebar-tab', gameInstance.bettingType === 'manual' ? 'active' : '']" @click="setBettingType('manual')">
                    {{ $t('general.bets.manual') }}
                </div>
                <div v-if="gameInstance.game && gameInstance.game.autoBetSettings.supported"
                    :class="['game-sidebar-tab', gameInstance.bettingType === 'auto' ? 'active' : '']" @click="setBettingType('auto')">
                    {{ $t('general.bets.auto') }}
                </div>
            </div>
            <component v-for="(c, i) in components" :key="i" :is="'sidebar-' + c.name" :data="c.data"></component>
        </div>
    </div>
</template>

<script>
import { useAppStore } from '../../../stores/app';
import SidebarLabel from './SidebarLabel.vue';
import SidebarInput from './SidebarInput.vue';
import SidebarSelect from './SidebarSelect.vue';
import SidebarButtons from './SidebarButtons.vue';
import SidebarWagerClassic from './SidebarWagerClassic.vue';
import SidebarWagerChips from './SidebarWagerChips.vue';
import SidebarPlay from './SidebarPlay.vue';
import SidebarFooter from './SidebarFooter.vue';
import SidebarHistory from './SidebarHistory.vue';
import SidebarCustomHistory from './SidebarCustomHistory.vue';
import SidebarMultiplayerTable from './SidebarMultiplayerTable.vue';
import SidebarAutoBets from './SidebarAutoBets.vue';
import SidebarProfit from './SidebarProfit.vue';

export default {
    components: {
        'sidebar-label': SidebarLabel,
        'sidebar-input': SidebarInput,
        'sidebar-select': SidebarSelect,
        'sidebar-buttons': SidebarButtons,
        'sidebar-wager-classic': SidebarWagerClassic,
        'sidebar-wager-chips': SidebarWagerChips,
        'sidebar-play': SidebarPlay,
        'sidebar-footer': SidebarFooter,
        'sidebar-history': SidebarHistory,
        'sidebar-custom-history': SidebarCustomHistory,
        'sidebar-multiplayer-table': SidebarMultiplayerTable,
        'sidebar-auto-bets': SidebarAutoBets,
        'sidebar-profit': SidebarProfit
    },
    data() {
        return { components: [] };
    },
    computed: {
        gameInstance() { return useAppStore().gameInstance; }
    },
    methods: {
        setBettingType(type) {
            if (this.gameInstance.game.autoBetSettings.state || this.gameInstance.playTimeout) return;
            this.updateGameInstance((i) => i.bettingType = type);
            const btn = document.querySelector('.play-button');
            if (btn) btn.classList.remove('disabled');
        }
    },
    mounted() {
        const ref = this.$parent && this.$parent.$refs.gameComponent;
        if (ref && ref.getSidebarComponents) this.components = ref.getSidebarComponents();
    },
    updated() {
        const ref = this.$parent && this.$parent.$refs.gameComponent;
        if (ref && ref.getSidebarComponents && this.components.length === 0) {
            this.components = ref.getSidebarComponents();
        }
    }
};
</script>

<style lang="scss">
.game-sidebar {
    .game-sidebar-sticky {
        position: sticky;
        top: 70px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        padding-bottom: 30px;
    }

    .game-sidebar-tabs {
        display: flex;
        gap: 6px;
        margin-bottom: 6px;

        .game-sidebar-tab {
            flex: 1;
            text-align: center;
            padding: 9px;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            font-size: .85em;
            background: rgba(255, 255, 255, .06);

            &.active {
                background: #e51f49;
                color: #fff;
            }
        }
    }
}
</style>
