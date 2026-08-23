<template>
    <div class="container-fluid position-relative">
        <!-- Guest Auth Required Overlay -->
        <div class="game-auth-gate" v-if="!store.user">
            <div class="auth-gate-card">
                <div class="gate-icon"><i class="fas fa-lock"></i></div>
                <h3>Sign in to Play</h3>
                <p>Join RATINO to place real bets with Chips, chat with players, and win.</p>
                <div class="gate-buttons">
                    <button class="btn btn-gate-login" @click="openAuth('login')">Sign In</button>
                    <button class="btn btn-gate-register" @click="openAuth('register')">Register (+10k Chips)</button>
                </div>
            </div>
        </div>

        <div :class="`game-container game-${$route.params.id}`" v-if="store.user">
            <div class="row align-items-stretch">
                <div class="col-12 col-lg-3">
                    <div class="game-sidebar-container">
                        <game-sidebar></game-sidebar>
                    </div>
                </div>
                <div class="col-12 col-lg-9">
                    <div class="game-stage-container">
                        <div :class="`game-content game-content-${$route.params.id} game-type-local`">
                            <component ref="gameComponent" :is="component" :key="$route.params.id" v-if="component"></component>
                            <div v-else class="game-missing">
                                <i class="fas fa-dice"></i>
                                <div>Game not found</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="game-footer" v-show="store.user"></div>

        <!-- Realtime Fast Reactions Floating Bar (Only in shared/multiplayer rooms for logged-in players) -->
        <fast-reactions v-if="store.user && isSharedRoom" :channel="sharedRoomChannel"></fast-reactions>
    </div>
</template>

<script>
import { defineAsyncComponent } from 'vue';
import GameSidebar from './sidebar/GameSidebar.vue';
import FastReactions from '../ui/FastReactions.vue';
import { useAppStore } from '../../stores/app';
import { useModal } from '../../composables/useModal';
import bus from '../../lib/bus';

const games = {
    mines: defineAsyncComponent(() => import('./games/Mines.vue')),
    dice: defineAsyncComponent(() => import('./games/Dice.vue')),
    wheel: defineAsyncComponent(() => import('./games/Wheel.vue')),
    plinko: defineAsyncComponent(() => import('./games/Plinko.vue')),
    coinflip: defineAsyncComponent(() => import('./games/Coinflip.vue')),
    tower: defineAsyncComponent(() => import('./games/Tower.vue')),
    keno: defineAsyncComponent(() => import('./games/Keno.vue')),
    stairs: defineAsyncComponent(() => import('./games/Stairs.vue')),
    blackjack: defineAsyncComponent(() => import('./games/Blackjack.vue')),
    diamonds: defineAsyncComponent(() => import('./games/Diamonds.vue')),
    roulette: defineAsyncComponent(() => import('./games/Roulette.vue')),
    crash: defineAsyncComponent(() => import('./games/Crash.vue')),
    hilo: defineAsyncComponent(() => import('./games/Hilo.vue')),
    limbo: defineAsyncComponent(() => import('./games/Limbo.vue')),
    slide: defineAsyncComponent(() => import('./games/Slide.vue')),
    baccarat: defineAsyncComponent(() => import('./games/Baccarat.vue')),
    videopoker: defineAsyncComponent(() => import('./games/Videopoker.vue')),
    slots: defineAsyncComponent(() => import('./games/Slots.vue'))
};

export default {
    components: { GameSidebar, FastReactions },
    setup() {
        return { store: useAppStore() };
    },
    computed: {
        component() {
            return games[this.$route.params.id] || null;
        },
        isSharedRoom() {
            return Boolean(this.$route.query.room || localStorage.getItem('ratino_active_room'));
        },
        sharedRoomChannel() {
            if (this.$route.query.room) return `room.${this.$route.query.room}`;
            try {
                const saved = JSON.parse(localStorage.getItem('ratino_active_room') || '{}');
                if (saved && saved.code) return `room.${saved.code}`;
            } catch (e) {}
            return `multiplayer.${this.$route.params.id}`;
        }
    },
    methods: {
        openAuth(type) {
            useModal().open('Auth', { type });
        },
        wire(ref) {
            window.$gameRef = ref;

            this.updateGameInstance((i) => {
                i.bettingType = 'manual';
                i.playTimeout = false;
            });

            this.createGameInstance(this.$route.params.id, ref);
            bus.emit('game:wired', ref);
        },
        awaitRef(attempts = 0) {
            if (this.$refs.gameComponent) {
                this.wire(this.$refs.gameComponent);
                return;
            }
            if (attempts > 60) return;
            setTimeout(() => this.awaitRef(attempts + 1), 80);
        },
        forwardMultiplayer(event, gameIdOf, name) {
            bus.on(event, (e) => {
                const id = gameIdOf(e);
                const ref = window.$gameRef;
                if (!ref || !ref.multiplayerEvent) return;
                if (id !== undefined && id !== null && String(id) !== String(this.$route.params.id)) return;
                ref.multiplayerEvent(name, e);
            });
        }
    },
    watch: {
        '$route.params.id'(newId) {
            if (newId) {
                this.updateGameInstance((i) => {
                    i.playTimeout = false;
                    i.bettingType = 'manual';
                });
                this.$nextTick(() => {
                    this.awaitRef();
                });
            }
        },
        'store.user'(newUser) {
            if (newUser) {
                this.$nextTick(() => {
                    this.awaitRef();
                });
            }
        }
    },
    mounted() {
        if (!this.component) {
            this.$router.push('/');
            return;
        }

        this.awaitRef();

        this.forwardMultiplayer('event:multiplayerBettingStateChange', (e) => e.game, 'MultiplayerBettingStateChange');
        this.forwardMultiplayer('event:multiplayerTimerStart', (e) => e.game, 'MultiplayerTimerStart');
        this.forwardMultiplayer('event:multiplayerGameFinished', (e) => e.game, 'MultiplayerGameFinished');
        this.forwardMultiplayer('event:multiplayerDataUpdate', (e) => e.game, 'MultiplayerDataUpdate');
        this.forwardMultiplayer('event:multiplayerGameBet', (e) => e.game && e.game.game, 'MultiplayerGameBet');
        this.forwardMultiplayer('event:multiplayerBetCancellation', (e) => e.game && e.game.game, 'MultiplayerBetCancellation');

        // auto-bet continuation on round start
        bus.on('event:multiplayerTimerStart', (e) => {
            const gi = this.gameInstance;
            if (gi && gi.game && gi.game.id === e.game && gi.bettingType === 'auto' && gi.game.autoBetSettings && gi.game.autoBetSettings.state) {
                if (typeof gi.game.autoBetSettings.next === 'function') gi.game.autoBetSettings.next();
            }
        });

        // own-game results feed session stats
        bus.on('event:multiplayerGameFinished', () => {});
    },
    beforeUnmount() {
        window.$gameRef = null;
        this.updateGameInstance((i) => {
            if (i) {
                i.playTimeout = false;
                i.game = null;
            }
        });
        const footer = document.querySelector('.game-footer');
        if (footer) footer.innerHTML = '';
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.game-auth-gate {
    display: flex;
    align-items: center;
    justify-content: center;
    min-height: calc(100vh - 220px);
    width: 100%;
    margin: 0 auto;
    padding: 20px 16px;
    background: transparent;

    .auth-gate-card {
        background: #1c1d25;
        border: 1px solid rgba(255, 255, 255, 0.08);
        box-shadow: 0 16px 40px rgba(0, 0, 0, 0.6);
        padding: 44px 36px;
        border-radius: 16px;
        text-align: center;
        max-width: 440px;
        width: 100%;
        margin: auto;

        .gate-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: rgba(229, 31, 73, 0.12);
            color: #e51f49;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin: 0 auto 16px;
            box-shadow: none;
        }

        h3 {
            font-size: 1.5em;
            font-weight: 700;
            color: #fff;
            margin-bottom: 8px;
        }

        p {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.6);
            margin-bottom: 22px;
            line-height: 1.5;
        }

        .gate-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;

            .btn {
                padding: 12px 20px;
                border-radius: 8px;
                font-weight: 700;
                font-size: 0.95em;
                cursor: pointer;
                transition: all 0.2s ease;
            }

            .btn-gate-login {
                background: rgba(255, 255, 255, 0.1);
                color: #fff;
                border: 1px solid rgba(255, 255, 255, 0.2);

                &:hover {
                    background: rgba(255, 255, 255, 0.18);
                }
            }

            .btn-gate-register {
                background: #e51f49 !important;
                color: #fff !important;
                border: none !important;
                box-shadow: none !important;

                &:hover {
                    background: #ff2a55 !important;
                    box-shadow: none !important;
                    transform: none;
                }
            }
        }
    }
}

.game-locked {
    filter: blur(4px) grayscale(0.5);
    pointer-events: none;
    user-select: none;
}

.game-stage-container {
    background: rgba(18, 19, 24, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    position: relative;
    min-height: 520px;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
}

.game-sidebar-container {
    background: rgba(18, 19, 24, 0.95);
    border: 1px solid rgba(255, 255, 255, 0.08);
    border-radius: 16px;
    padding: 16px;
    box-shadow: 0 16px 40px rgba(0, 0, 0, 0.45), inset 0 1px 0 rgba(255, 255, 255, 0.05);
    height: 100%;
}

.game-content {
    min-height: 420px;
    position: relative;
    width: 100%;
}

.game-footer {
    position: fixed;
    bottom: 20px;
    left: $sidebar-width;
    right: 0;
    margin: 0 auto;
    width: fit-content;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 6px;
    padding: 6px 8px;
    border-radius: 10px;
    background: rgba(18, 19, 25, 0.94);
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.55);
    backdrop-filter: blur(16px);
    z-index: 1500;

    .action {
        cursor: pointer;
        opacity: .7;
        transition: all .2s ease;
        font-size: 1.15em;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.06);
        color: #fff;
        padding: 0;

        &:hover {
            opacity: 1;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.12);
            color: #fff;
            transform: none;
        }

        &.active, i.active {
            opacity: 1;
            color: #e51f49;
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.1);
        }
    }
}

@media(max-width: 991px) {
    .game-sidebar-container {
        margin-bottom: 16px;
        height: auto;
    }

    .game-stage-container {
        padding: 16px 10px;
        min-height: 380px;
    }

    .game-footer {
        left: 0;
        bottom: 14px;
        padding: 6px 12px;

        .action {
            width: 44px;
            height: 44px;
            font-size: 1.35em;
        }
    }
}

.game-missing {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 400px;
    opacity: .5;

    i { font-size: 3em; margin-bottom: 14px; }
}
</style>
