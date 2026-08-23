<template>
    <div class="chat-root">
        <div class="chat-tabs">
            <div :class="['chat-tab', channel === 'global' ? 'active' : '']" @click="switchChannel('global')">
                <i class="fas fa-globe"></i> {{ $t('chat.global') }}
            </div>
            <div v-for="room in rooms" :key="room.code" :class="['chat-tab', channel === room.code ? 'active' : '']" @click="switchChannel(room.code)">
                {{ room.name }}
                <i class="fas fa-times chat-room-leave" @click.stop="leaveRoom(room.code)"></i>
            </div>
            <div class="chat-tab chat-add" @click="showRoomForm = !showRoomForm"><i class="fas fa-plus"></i></div>
        </div>

        <div class="chat-room-form" v-if="showRoomForm">
            <input v-model="roomName" type="text" :placeholder="$t('chat.room_name')" maxlength="32" @keyup.enter="createRoom">
            <button class="btn btn-primary btn-sm" @click="createRoom">{{ $t('chat.create_room') }}</button>
            <input v-model="roomCode" type="text" :placeholder="$t('chat.room_code')" maxlength="5" @keyup.enter="joinRoom">
            <button class="btn btn-secondary btn-sm" @click="joinRoom">{{ $t('chat.join_room') }}</button>
        </div>

        <div class="chat-online" v-if="members.length">
            <span class="chat-online-label"><i class="fas fa-circle" style="color: #2fd672; font-size: 0.5em;"></i> {{ members.length }}</span>
            <div v-for="(u, i) in members" :key="i" class="chat-online-user" :title="u.name">
                <img :src="u.avatar || '/img/misc/incognito-default.png'" :style="{ borderColor: vipColor(u.vipLevel || 0) }" alt>
            </div>
        </div>

        <div class="chat-messages" ref="messages">
            <div v-for="(message, i) in messages" :key="i">
                <div v-if="message.type === 'rain'" class="chat-rain">
                    <i class="fas fa-cloud-rain"></i>
                    <span v-if="message.data.funder">{{ $t('chat.rain_funded', { name: message.data.funder }) }}</span>
                    <span v-else>{{ $t('chat.rain_global') }}</span>
                    — {{ Number(message.data.reward).toLocaleString() }} $CHEDDAR
                    <span class="chat-rain-users" v-if="message.data.users && message.data.users.length">× {{ message.data.users.length }}</span>
                </div>
                <div v-else-if="message.type === 'tip'" class="chat-tip">
                    <i class="fas fa-hand-holding-usd"></i>
                    <b>{{ message.data.from }}</b> → <b>{{ message.data.to }}</b>:
                    <span class="text-success">{{ Number(message.data.amount).toLocaleString() }} $CHEDDAR</span>
                </div>
                <div v-else class="chat-message">
                    <img :src="message.user.avatar || '/img/misc/incognito-default.png'" :style="{ borderColor: vipColor(message.vipLevel) }" alt>
                    <div class="chat-message-body">
                        <span class="chat-message-name" :style="{ color: vipColor(message.vipLevel) }">{{ message.user.name }}</span>
                        <span class="chat-message-text">{{ message.data }}</span>
                    </div>
                </div>
            </div>
            <div class="chat-empty" v-if="!messages.length">
                <i class="fas fa-comments"></i>
                <div>{{ $t('chat.empty') }}</div>
            </div>
        </div>

        <div class="chat-input">
            <input v-model="message" type="text" :placeholder="$t('chat.placeholder')" maxlength="400" @keyup.enter="send">
            <button class="btn btn-primary" @click="send" :disabled="!message.length">{{ $t('chat.send') }}</button>
        </div>
        <div class="chat-hint">{{ $t('chat.tip_hint') }}</div>
        <div class="chat-rainbar">
            <input v-model="rainAmount" type="number" min="1" :placeholder="$t('chat.rain_amount')">
            <button class="btn btn-secondary btn-sm" @click="fundRain">{{ $t('chat.rain_fund') }}</button>
        </div>
    </div>
</template>

<script>
import { whisper } from '../../lib/whisper';
import bus from '../../lib/bus';
import { chatChannel, leaveChatChannel } from '../../lib/echo';
import { useAppStore } from '../../stores/app';

const VIP_COLORS = ['#a0765a', '#b9c0c9', '#e8c53c', '#9bd1e8', '#58d1f2', '#b06bf0', '#ffd23f'];

export default {
    data() {
        return {
            channel: 'global',
            rooms: [],
            messages: [],
            members: [],
            message: '',
            roomName: '',
            roomCode: '',
            showRoomForm: false,
            rainAmount: ''
        };
    },
    methods: {
        vipColor(level) { return VIP_COLORS[level] || VIP_COLORS[0]; },
        switchChannel(channel) {
            leaveChatChannel(this.channel);
            this.channel = channel;
            this.messages = [];
            this.members = [];
            this.fetch();
            this.subscribe();
        },
        subscribe() {
            const store = useAppStore();
            const ch = chatChannel(this.channel);
            if (!ch) return;
            ch.here((users) => this.members = users);
            ch.joining((u) => { if (!this.members.find((m) => m.name === u.name)) this.members.push(u); });
            ch.leaving((u) => this.members = this.members.filter((m) => m.name !== u.name));
        },
        fetch() {
            whisper('ChatHistory', { channel: this.channel }).then((response) => {
                this.messages = response.messages;
                this.scroll();
            }).catch(() => {});
        },
        scroll() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        },
        send() {
            if (!this.message.length) return;
            whisper('ChatMessage', { message: this.message, channel: this.channel }).then(() => {
                this.message = '';
                this.fetch();
            });
        },
        createRoom() {
            if (!this.roomName.length) return;
            whisper('Room', { action: 'create', name: this.roomName }).then((room) => {
                this.roomName = '';
                this.showRoomForm = false;
                this.loadRooms();
                this.switchChannel(room.code);
            });
        },
        joinRoom() {
            if (!this.roomCode.length) return;
            whisper('Room', { action: 'join', code: this.roomCode }).then((room) => {
                this.roomCode = '';
                this.showRoomForm = false;
                this.loadRooms();
                this.switchChannel(room.code);
            }).catch(() => this.$toast.error(this.$t('chat.join_failed')));
        },
        leaveRoom(code) {
            whisper('Room', { action: 'leave', code }).then(() => {
                if (this.channel === code) this.switchChannel('global');
                this.loadRooms();
            });
        },
        loadRooms() {
            whisper('Room', { action: 'list' }).then((response) => this.rooms = response.rooms);
        },
        fundRain() {
            const amount = parseFloat(this.rainAmount);
            if (!amount || amount <= 0) return;
            whisper('Rain', { action: 'fund', amount }).then((response) => {
                if (response.funded) {
                    this.$toast.success(this.$t('chat.rain_sent', { share: response.share }));
                    this.rainAmount = '';
                    useAppStore().updateData();
                    this.fetch();
                }
            }).catch((code) => {
                if (code === 3) this.$toast.error(this.$t('chat.rain_balance'));
                else if (code === 4) this.$toast.error(this.$t('chat.rain_nobody'));
            });
        },
        onChatMessage(e) {
            this.messages.push(e);
            if (this.messages.length > 80) this.messages.shift();
            this.scroll();
        }
    },
    mounted() {
        this.loadRooms();
        this.fetch();
        this.subscribe();
        bus.on('event:chatMessage', this.onChatMessage);
    },
    beforeUnmount() {
        bus.off('event:chatMessage', this.onChatMessage);
        leaveChatChannel(this.channel);
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.chat-root {
    display: flex;
    flex-direction: column;
    height: 62vh;
    min-height: 380px;

    .chat-tabs {
        display: flex;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 12px;

        .chat-tab {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: .8em;
            font-weight: 600;
            cursor: pointer;
            background: rgba(255, 255, 255, .06);

            &.active {
                background: #e51f49;
                color: #fff;
            }

            .chat-room-leave { font-size: .8em; opacity: .5; }

            &.chat-add {
                background: transparent;
                border: 1px dashed rgba(255, 255, 255, .2);
            }
        }
    }

    .chat-room-form {
        display: grid;
        grid-template-columns: 1fr auto 1fr auto;
        gap: 8px;
        margin-bottom: 12px;
    }

    .chat-online {
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px;
        margin-bottom: 10px;

        .chat-online-label { font-size: .75em; opacity: .7; margin-right: 4px; }

        .chat-online-user img {
            width: 26px;
            height: 26px;
            border-radius: 50%;
            border: 2px solid transparent;
        }
    }

    .chat-messages {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 10px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);
        border-radius: 6px;
        padding: 12px;
        min-height: 0;

        .chat-message {
            display: flex;
            gap: 10px;
            align-items: flex-start;

            img {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                border: 2px solid transparent;
                flex-shrink: 0;
            }

            .chat-message-body { display: flex; flex-direction: column; }
            .chat-message-name { font-size: .8em; font-weight: 700; margin-bottom: 2px; }
            .chat-message-text { font-size: .9em; word-break: break-word; }
        }

        .chat-tip {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .85em;
            background: rgba(223, 52, 79, .08);
            border-left: 3px solid #e51f49;
            padding: 8px 10px;
            border-radius: 4px;

            i { color: #e51f49; }
        }

        .chat-rain {
            display: flex;
            align-items: center;
            gap: 6px;
            font-size: .85em;
            background: rgba(88, 209, 242, .08);
            border-left: 3px solid #58d1f2;
            padding: 8px 10px;
            border-radius: 4px;

            i { color: #58d1f2; }
        }

        .chat-empty {
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: .35;

            i { font-size: 2em; margin-bottom: 8px; }
        }
    }

    .chat-input {
        display: flex;
        gap: 8px;
        margin-top: 10px;

        input { flex: 1; }
    }

    .chat-hint { font-size: .72em; opacity: .5; margin-top: 6px; }

    .chat-rainbar {
        display: flex;
        gap: 8px;
        margin-top: 10px;

        input { flex: 1; }
    }
}

@media(max-width: 620px) {
    .chat-room-form { grid-template-columns: 1fr; }
}
</style>
