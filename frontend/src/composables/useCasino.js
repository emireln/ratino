import { useAppStore } from '../stores/app';
import { pinia } from '../stores/pinia';
import { whisper } from '../lib/whisper';
import bus from '../lib/bus';
import { toast } from '../lib/toast';
import { Howl } from 'howler';

const store = useAppStore(pinia);

const soundTimeouts = [];

const $store = {
    dispatch: (name, ...args) => {
        if (typeof store[name] === 'function') return store[name](...args);
        return Promise.resolve();
    },
    commit: (name, ...args) => {
        if (typeof store[name] === 'function') return store[name](...args);
        return undefined;
    },
    getters: store,
    state: store
};

/**
 * Global game mixin (Options API). `computed` must be plain getter
 * functions — spread directly into app.mixin.
 */
export function useCasino() {
    return {
        computed: {
            user() { return store.user; },
            isGuest() { return !store.user; },
            demo() { return store.user ? store.demo : true; },
            unit() { return store.unit; },
            currency() { return store.currency; },
            currencies() { return store.currencies; },
            sound() { return store.sound; },
            quick() { return store.quick; },
            games() { return store.games; },
            gameInstance() { return store.gameInstance; },
            sessionStats() { return store.sessionStats; }
        },
        methods: {
            $store,
            $bus: bus,
            $toast: toast,

            whisper(event, request = {}) {
                return whisper(event, request);
            },

            createGameInstance(id, ref) {
                let instance = store.gameInstance ?? { game: null, history: null, bettingType: 'manual', playTimeout: false };
                instance.game = {
                    id,
                    data: {},
                    autoBetSettings: {
                        supported: false,
                        state: false,
                        games: 0,
                        stopOnWin: false,
                        currentIteration: 0,
                        initialBet: 0,
                        timeout: 0,
                        win: { action: 'reset', value: 0 },
                        loss: { action: 'reset', value: 0 }
                    },
                    bettingType: 'manual'
                };
                store.setGameInstance(instance);

                if (ref.restore) whisper('RestoreData', { api_id: id }).then((response) => {
                    this.updateGameInstance((i) => {
                        i.game.extendedId = response.game._id;
                        i.game.extendedState = 'in-progress';
                        i.game.profit = null;
                        i.game.currentProfit = response.game.wager * response.game.multiplier;
                        i.playTimeout = false;
                        i.bet = response.game.wager;
                    });
                    ref.restore(response);
                }).catch(() => {});

                if (ref.gameDataRetrieved) whisper('GameData', { api_id: id }).then((response) => {
                    const instance = store.gameInstance;
                    instance.game.data = response;
                    store.setGameInstance(instance);
                    ref.gameDataRetrieved(instance.game.data);
                }).catch(() => {});

                if (ref.afterMount) ref.afterMount();
            },

            updateGameInstance(tap) {
                const game = { ...store.gameInstance };
                tap(game);
                store.setGameInstance(game);
            },

            capitalize(string) {
                return [].map.call(string, (char, i) => i ? char : char.toUpperCase()).join('');
            },

            applyHouseEdge(payout) {
                const game = store.games.filter((e) => e.id === store.gameInstance.game.id)[0];
                return game && game.houseEdge ? (payout * (1 - game.houseEdge / 100)).toFixed(2) : payout.toFixed(2);
            },

            rawBitcoin(to, value) {
                return value ? value.toFixed(2) : (0).toFixed(2);
            },

            usdToToken(price, usd) {
                return usd / price;
            },

            tokenToUsd(price, token) {
                return token * price;
            },

            sendTurn(data, callback, finishedCallback = null) {
                this.whisper('Turn', { id: this.extendedGameId(), data })
                    .then((response) => {
                        callback(response);

                        if (response.type === 'fail') return;

                        if (store.gameInstance.bettingType === 'manual' && response.type === 'continue') {
                            this.updateGameInstance((i) => i.game.currentProfit = (i.bet * response.game.multiplier).toFixed(2));
                        }
                        if (response.type === 'lose' || response.type === 'finish') this.pushStats(response.game);
                    })
                    .catch((error) => {
                        switch (error) {
                            case 0: this.sendTurn(data, callback, finishedCallback); break;
                            case 1: toast.error('Invalid game id'); break;
                            case 2: if (finishedCallback != null) finishedCallback(); break;
                            case 3: toast.error('Invalid API operation'); break;
                        }
                    });
            },

            playSound(src, timeout = null) {
                if (timeout != null) {
                    let cancel = false;
                    soundTimeouts.forEach((t) => {
                        if (t.src === src && +new Date() < t.time) cancel = true;
                    });

                    if (cancel) return;

                    soundTimeouts.push({ src, time: +new Date() + timeout });
                }

                if (!store.sound) return;
                try {
                    new Howl({ src: [src], volume: 0.3 }).play();
                } catch (e) {}
            },

            chain(times, ms, callback) {
                let i = 0;

                const next = () => {
                    if (i < times) {
                        setTimeout(() => {
                            callback(i);
                            next();
                        }, ms);
                        i++;
                    }
                };

                next();
            },

            resultPopup(game) {
                if (game.status !== undefined && game.status === 'cancelled') return;

                if (game.win !== undefined) this.pushStats(game);

                const status = game.profit === 0 && game.multiplier === 0 ? 'lose'
                    : (game.status === undefined ? (game.win ? 'win' : 'lose')
                        : (game.status === 'lose' ? 'lose' : 'win'));

                const el = document.createElement('div');
                el.className = `resultPopup resultPopup-${status}`;
                el.innerHTML = `
                    ${store.user && store.demo ? '<div class="demoHeader">DEMO</div>' : ''}
                    <div class="multiplier">${status === 'lose' && game.multiplier >= 1 ? (0).toFixed(2) : (game.multiplier ?? 0).toFixed(2)}x</div>
                    <div class="divider"></div>
                    <div class="profit">${(game.profit ?? 0).toFixed(2)} <i class="fas fa-coins"></i></div>
                `;

                const content = document.querySelector('.game-content');
                if (content) {
                    content.appendChild(el);
                    setTimeout(() => el.remove(), 3000);
                }
            },

            random(min, max, floor = true) {
                const r = Math.random() * max + min;
                return floor ? Math.floor(r) : r;
            },

            setCookie(cname, cvalue, exdays) {
                const d = new Date();
                d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
                document.cookie = `${cname}=${cvalue};expires=${d.toUTCString()};path=/`;
            },

            getCookie(cname) {
                const name = `${cname}=`;
                const decoded = decodeURIComponent(document.cookie);
                const ca = decoded.split(';');
                for (let i = 0; i < ca.length; i++) {
                    let c = ca[i];
                    while (c.charAt(0) === ' ') c = c.substring(1);
                    if (c.indexOf(name) === 0) return c.substring(name.length, c.length);
                }
                return '';
            },

            isOverview(overviewData) {
                return overviewData != null;
            },

            abbreviate(value) {
                const length = value.toFixed(0).length;
                const index = Math.ceil((length - 3) / 3);
                const suffix = ['k', 'm', 'g', 't'];

                if (length < 4) return value.toFixed(4).replace(/0{1,2}$/, '');

                return (value / Math.pow(1000, index)).toFixed(1).replace(/\.0$/, '') + suffix[index - 1];
            },

            pushStats(game) {
                if (!game) return;
                if (store.sessionStats.startedAt == null) store.resetSessionStats();
                store.pushSessionGame(game);
                bus.emit('profitMonitoring:pushStats', game);
            },

            isExtendedGameStarted() {
                return store.gameInstance.game && store.gameInstance.game.extendedState === 'in-progress';
            },

            finishExtended(sendServerRequest = true, requestCallback = null) {
                const changeState = (response) => {
                    this.updateGameInstance((i) => i.playTimeout = false);

                    if (response && response.game.status !== 'cancelled') this.pushStats(response.game);
                    this.updateGameInstance((i) => i.game.extendedState = 'finished');

                    if (window.$gameRef && window.$gameRef.callback) window.$gameRef.callback(response);
                };

                if (sendServerRequest) {
                    this.updateGameInstance((i) => i.playTimeout = true);

                    this.whisper('Finish', { id: this.extendedGameId() }).then((response) => {
                        changeState(response);
                        if (requestCallback) requestCallback(response);
                    }).catch(() => {
                        if (requestCallback) requestCallback({ game: { status: 'lose' } });
                        changeState(null);
                    });
                } else changeState(null);
            },

            extendedGameId() {
                return store.gameInstance.game.extendedId;
            }
        }
    };
}

export { $store, store };