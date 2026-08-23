<template>
    <div class="sidebar">
        <div class="fixed">
            <div class="sidebar-brand-mark">
                <router-link to="/" title="RATINO">
                    <img src="/favicon-64.png" alt="R" class="brand-r">
                </router-link>
            </div>
            <div class="sidebar-games-list">
                <router-link v-for="game in visibleGames" :key="game.id"
                    :to="`/casino/game/${game.id}`" class="game"
                    @mouseenter="onHover(game.name, $event)"
                    @mouseleave="onLeave">
                    <icon :icon="game.icon"></icon>
                </router-link>
            </div>
        </div>

        <!-- Floating Tooltip Immune to Parent Overflow Clipping -->
        <teleport to="body">
            <transition name="fade">
                <div class="sidebar-floating-tooltip" v-if="hoveredText" :style="{ top: tooltipTop + 'px' }">
                    {{ hoveredText }}
                </div>
            </transition>
        </teleport>
    </div>
</template>

<script>
import { useAppStore } from '../stores/app';

export default {
    setup() {
        return { store: useAppStore() };
    },
    data() {
        return {
            hoveredText: null,
            tooltipTop: 0,
            hoverTimer: null
        };
    },
    computed: {
        visibleGames() {
            return (this.store.games || []).filter((g) => g && !g.isDisabled);
        }
    },
    methods: {
        onHover(text, event) {
            clearTimeout(this.hoverTimer);
            const rect = event.currentTarget.getBoundingClientRect();
            const top = rect.top + (rect.height / 2);

            this.hoverTimer = setTimeout(() => {
                this.hoveredText = text;
                this.tooltipTop = top;
            }, 600);
        },
        onLeave() {
            clearTimeout(this.hoverTimer);
            this.hoveredText = null;
        }
    },
    beforeUnmount() {
        clearTimeout(this.hoverTimer);
    }
};
</script>

<style lang="scss">
@import '../sass/variables';

.sidebar {
    width: $sidebar-width;
    flex-shrink: 0;

    .fixed {
        position: fixed;
        top: 0;
        left: 0;
        width: $sidebar-width;
        height: 100vh;
        padding: 12px 0 20px;
        overflow-y: auto;
        overflow-x: hidden;
        z-index: 4000;
        display: flex;
        flex-direction: column;
        align-items: center;

        background: rgba(18, 19, 24, 0.95);
        border-right: none;
        backdrop-filter: blur(20px);

        scrollbar-width: none;
        &::-webkit-scrollbar { display: none; }
    }

    .sidebar-brand-mark {
        display: flex;
        align-items: center;
        justify-content: center;
        height: 48px;
        width: 100%;
        margin-bottom: 10px;

        a {
            display: flex;
            align-items: center;
            justify-content: center;
            transition: transform 0.2s ease;

            &:hover {
                transform: scale(1.08);
            }
        }

        .brand-r {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.35);
        }
    }

    .sidebar-games-list {
        width: 100%;
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 2px;
    }

    .game {
        position: relative;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 42px;
        width: 40px;
        border-radius: 8px;
        opacity: .55;
        transition: all .2s ease;
        color: #fff;
        text-decoration: none;

        &:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
        }

        &.router-link-active,
        &.router-link-exact-active {
            opacity: 1;
            background: rgba(255, 255, 255, 0.08);
            color: #e51f49;

            i, svg { color: #e51f49; }
        }

        i, svg {
            width: 20px;
            text-align: center;
            font-size: 1.1em;
            display: flex;
            align-items: center;
            justify-content: center;
            color: inherit;
            transition: color 0.2s ease;
        }
    }
}

.sidebar-floating-tooltip {
    position: fixed;
    left: 60px;
    transform: translateY(-50%);
    background: #24252e;
    border: 1px solid rgba(255, 255, 255, .15);
    color: #fff;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: .82em;
    font-weight: 600;
    white-space: nowrap;
    pointer-events: none;
    z-index: 99999;
    box-shadow: 0 8px 24px rgba(0, 0, 0, .55);

    &:after {
        content: '';
        position: absolute;
        right: 100%;
        top: 50%;
        margin-top: -5px;
        border: 5px solid transparent;
        border-right-color: #24252e;
    }
}

.fade-enter-active, .fade-leave-active {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
.fade-enter-from, .fade-leave-to {
    opacity: 0;
    transform: translateY(-50%) translateX(-4px);
}
</style>
