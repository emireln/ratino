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
            <div class="row">
                <div class="col-12 col-lg-3">
                    <game-sidebar></game-sidebar>
                </div>
                <div class="col-12 col-lg-9">
                    <div :class="`game-content game-content-${$route.params.id} game-type-local`">
                        <component ref="gameComponent" :is="component" v-if="component"></component>
                        <div v-else class="game-missing">
                            <i class="fas fa-dice"></i>
                            <div>Game not found</div>
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
    mines: () => import('./games/Mines.vue'),
    dice: () => import('./games/Dice.vue'),
    wheel: () => import('./games/Wheel.vue'),
    plinko: () => import('./games/Plinko.vue'),
    coinflip: () => import('./games/Coinflip.vue'),
    tower: () => import('./games/Tower.vue'),
    keno: () => import('./games/Keno.vue'),
    stairs: () => import('./games/Stairs.vue'),
    blackjack: () => import('./games/Blackjack.vue'),
    diamonds: () => import('./games/Diamonds.vue'),
    roulette: () => import('./games/Roulette.vue'),
    crash: () => import('./games/Crash.vue'),
    hilo: () => import('./games/Hilo.vue'),
    limbo: () => import('./games/Limbo.vue'),
    slide: () => import('./games/Slide.vue'),
    baccarat: () => import('./games/Baccarat.vue'),
    videopoker: () => import('./games/Videopoker.vue'),
    slots: () => import('./games/Slots.vue')
};

export default {
    components: { GameSidebar, FastReactions },
    setup() {
        return { store: useAppStore() };
    },
    computed: {
        component() {
            return games[this.$route.params.id] ? defineAsyncComponent(games[this.$route.params.id]) : null;
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
        },
        awaitRef(attempts = 0) {
            if (this.$refs.gameComponent) {
                this.wire(this.$refs.gameComponent);
                return;
            }
            if (attempts > 50) return;
            setTimeout(() => this.awaitRef(attempts + 1), 100);
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
            if (gi.game && gi.game.id === e.game && gi.bettingType === 'auto' && gi.game.autoBetSettings.state) {
                if (typeof gi.game.autoBetSettings.next === 'function') gi.game.autoBetSettings.next();
            }
        });

        // own-game results feed session stats
        bus.on('event:multiplayerGameFinished', () => {});
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.game-auth-gate {
    position: absolute;
    inset: 0;
    z-index: 2500;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(14, 15, 20, 0.78);
    backdrop-filter: blur(8px);
    border-radius: 16px;
    padding: 20px;

    .auth-gate-card {
        background: #1c1d25;
        border: 1px solid rgba(229, 31, 73, 0.35);
        box-shadow: 0 20px 50px rgba(0, 0, 0, 0.7), 0 0 30px rgba(229, 31, 73, 0.2);
        padding: 36px 32px;
        border-radius: 16px;
        text-align: center;
        max-width: 440px;
        width: 100%;

        .gate-icon {
            width: 58px;
            height: 58px;
            border-radius: 50%;
            background: rgba(229, 31, 73, 0.15);
            color: #e51f49;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5em;
            margin: 0 auto 16px;
            box-shadow: 0 0 20px rgba(229, 31, 73, 0.3);
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

.game-content {
    min-height: 420px;
    position: relative;
}

.game-footer {
    position: fixed;
    bottom: 22px;
    left: $sidebar-width;
    right: 0;
    margin: 0 auto;
    width: fit-content;
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 8px;
    padding: 8px 16px;
    border-radius: 50px;
    background: rgba(18, 19, 25, 0.92);
    border: 1px solid rgba(255, 255, 255, 0.12);
    box-shadow: 0 12px 36px rgba(0, 0, 0, 0.55), 0 0 1px rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(16px);
    z-index: 1500;

    .action {
        cursor: pointer;
        opacity: .65;
        transition: all .25s ease;
        font-size: 1.55em;
        width: 50px;
        height: 50px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        background: transparent;
        border: none;
        color: #fff;
        padding: 0;

        &:hover {
            opacity: 1;
            background: rgba(229, 31, 73, 0.22);
            color: #fff;
            transform: translateY(-2px) scale(1.08);
        }

        &.active, i.active {
            opacity: 1;
            color: #e51f49;
        }
    }
}

@media(max-width: 991px) {
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
