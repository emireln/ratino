import Echo from 'laravel-echo';
import Pusher from 'pusher-js';
import bus from './bus';

let echo = null;

export function installEcho() {
    window.Pusher = Pusher;

    const token = localStorage.getItem('ratino_token');

    echo = new Echo({
        broadcaster: 'reverb',
        key: import.meta.env.VITE_REVERB_APP_KEY || 'ratino',
        wsHost: window.location.hostname || 'localhost',
        wsPort: 8080,
        wssPort: 8080,
        forceTLS: window.location.protocol === 'https:',
        enabledTransports: ['ws', 'wss'],
        auth: {
            headers: { Authorization: `Bearer ${token}` }
        }
    });

    window.Echo = echo;
    return echo;
}

export function connectRealtime(userId) {
    if (!echo || !userId) return;

    // private balance updates
    if (!window.__ratinoPrivate) {
        window.__ratinoPrivate = true;
        echo.private(`user.${userId}`).listen('.BalanceModification', (e) => {
            bus.emit('event:balanceModification', e);
        });
    }

    // live bets feed
    if (!window.__ratinoLive) {
        window.__ratinoLive = true;
        echo.channel('live').listen('.LiveBet', (e) => {
            bus.emit('event:liveBet', e);
        });
    }
}

export function multiplayerChannel(gameId) {
    if (!echo) return null;
    if (!window.__ratinoMulti) window.__ratinoMulti = {};
    if (window.__ratinoMulti[gameId]) return window.__ratinoMulti[gameId];

    const channel = echo.channel(`multiplayer.${gameId}`);
    window.__ratinoMulti[gameId] = channel;

    channel
        .listen('.MultiplayerBettingStateChange', (e) => bus.emit('event:multiplayerBettingStateChange', e))
        .listen('.MultiplayerTimerStart', (e) => bus.emit('event:multiplayerTimerStart', e))
        .listen('.MultiplayerGameBet', (e) => bus.emit('event:multiplayerGameBet', e))
        .listen('.MultiplayerGameFinished', (e) => bus.emit('event:multiplayerGameFinished', e))
        .listen('.MultiplayerBetCancellation', (e) => bus.emit('event:multiplayerBetCancellation', e))
        .listen('.MultiplayerDataUpdate', (e) => bus.emit('event:multiplayerDataUpdate', e));

    return channel;
}

export function chatChannel(channel, userId, userName, avatar, vipLevel) {
    if (!echo) return null;
    if (!window.__ratinoChat) window.__ratinoChat = {};
    if (window.__ratinoChat[channel]) return window.__ratinoChat[channel];

    const ch = echo.join(`chat.${channel}`);
    window.__ratinoChat[channel] = ch;

    ch.listen('.ChatMessage', (e) => bus.emit('event:chatMessage', e));

    return ch;
}

export function leaveChatChannel(channel) {
    if (window.__ratinoChat && window.__ratinoChat[channel]) {
        echo.leave(`chat.${channel}`);
        delete window.__ratinoChat[channel];
    }
}

export default echo;
