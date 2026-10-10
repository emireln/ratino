import { defineStore } from 'pinia';
import i18n from '../i18n';
import bus from '../lib/bus';
import client, { setToken } from '../lib/whisper';
import { disconnectEcho } from '../lib/echo';

export const useAppStore = defineStore('app', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('ratino_user') || 'null'),
        token: localStorage.getItem('ratino_token') || null,
        demo: false,
        locale: localStorage.getItem('ratino_locale') || (navigator.language.split('-')[0] || 'en'),
        theme: 'dark',
        unit: 'normal',
        currency: null,
        sound: true,
        quick: false,

        gameInstance: {},

        games: [],
        gamesLoading: false,
        gamesLoadError: false,
        currencies: [],
        recentGameHistory: [],

        sessionStats: {
            startedAt: null,
            points: [],
            profit: 0,
            wager: 0,
            wins: 0,
            losses: 0,
            biggestWin: 0,
            bestMultiplier: 0
        }
    }),

    getters: {
        isGuest: (state) => !state.user
    },

    actions: {
        setUserData(data) {
            this.user = data.user;
            this.token = data.token;
            localStorage.setItem('ratino_user', JSON.stringify(data.user));
            localStorage.setItem('ratino_token', data.token);
            setToken(data.token);
        },
        setUser(user) {
            this.user = user;
            localStorage.setItem('ratino_user', JSON.stringify(user));
        },
        logout() {
            this.user = null;
            this.token = null;
            localStorage.removeItem('ratino_user');
            localStorage.removeItem('ratino_token');
            setToken(null);
            disconnectEcho();
        },
        update() {
            if (!this.token) return;
            client.post('/api/auth/update').then(({ data }) => {
                this.user = data;
                localStorage.setItem('ratino_user', JSON.stringify(data));
            }).catch(() => {});
        },
        async updateData() {
            this.gamesLoading = true;
            this.gamesLoadError = false;
            const [gamesResult, currenciesResult] = await Promise.allSettled([
                client.post('/api/data/games'),
                client.post('/api/data/currencies')
            ]);

            if (gamesResult.status === 'fulfilled') this.games = gamesResult.value.data;
            else this.gamesLoadError = true;

            if (currenciesResult.status === 'fulfilled') {
                const data = currenciesResult.value.data;
                this.currencies = data;
                if (!this.currency || data[this.currency] === undefined) {
                    const firstCurrency = Object.values(data)[0];
                    if (firstCurrency) this.currency = firstCurrency.id;
                }
            }

            this.gamesLoading = false;
        },
        changeLocale(locale) {
            this.locale = locale;
            i18n.global.locale.value = locale;
            localStorage.setItem('ratino_locale', locale);
        },
        setDemo(status) { this.demo = status; },
        setUnit(unit) { this.unit = unit; },
        setCurrency(currency) { this.currency = currency; },
        setSoundState(state) { this.sound = state; },
        setQuickState(quick) { if (!this.gameInstance.playTimeout) this.quick = quick; },
        setGameInstance(instance) {
            this.gameInstance = instance;
        },
        pushRecentGame(id) {
            if (this.recentGameHistory.includes(id)) this.recentGameHistory = this.recentGameHistory.filter((e) => e !== id);
            this.recentGameHistory.push(id);
        },
        resetSessionStats() {
            this.sessionStats = {
                startedAt: Date.now(),
                points: [],
                profit: 0,
                wager: 0,
                wins: 0,
                losses: 0,
                biggestWin: 0,
                bestMultiplier: 0
            };
        },
        pushSessionGame(game) {
            const s = this.sessionStats;
            if (!s.startedAt) s.startedAt = Date.now();

            const profit = game.profit != null ? game.profit : 0;
            const wager = game.wager != null ? game.wager : 0;
            const multiplier = game.multiplier != null ? game.multiplier : 0;

            s.profit += profit - wager;
            s.wager += wager;
            if (profit > 0) s.wins++; else s.losses++;
            if (profit > s.biggestWin) s.biggestWin = profit;
            if (multiplier > s.bestMultiplier) s.bestMultiplier = multiplier;

            const last = s.points.length ? s.points[s.points.length - 1].y : 0;
            s.points.push({ x: s.points.length, y: +(last + profit - wager).toFixed(2) });
            if (s.points.length > 500) s.points.shift();
        }
    }
});
