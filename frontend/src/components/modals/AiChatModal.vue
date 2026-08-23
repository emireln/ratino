<template>
    <div class="ai-chat">
        <div class="ai-chat-header">
            <span>{{ $t('ai.subtitle') }}</span>
            <i class="fas fa-cog ai-cog" :title="$t('ai.settings')" @click="openSettings"></i>
        </div>

        <div class="ai-messages" ref="messages">
            <div v-for="(m, i) in messages" :key="i" :class="['ai-msg', m.role]">
                <div class="ai-msg-icon">
                    <i class="fas fa-user" v-if="m.role === 'user'"></i>
                    <img :src="`/img/misc/${provider === 'gemini' ? 'gemini-icon.svg' : 'openai-icon.svg'}`" alt="AI" class="ai-chat-avatar" v-else>
                </div>
                <div class="ai-msg-bubble">{{ m.content }}</div>
            </div>
            <div class="ai-msg ai-typing" v-if="typing">
                <div class="ai-msg-icon">
                    <img :src="`/img/misc/${provider === 'gemini' ? 'gemini-icon.svg' : 'openai-icon.svg'}`" alt="AI" class="ai-chat-avatar">
                </div>
                <div class="ai-msg-bubble ai-dots"><span></span><span></span><span></span></div>
            </div>
            <div class="ai-empty" v-if="!messages.length">
                <img :src="`/img/misc/${provider === 'gemini' ? 'gemini-icon.svg' : 'openai-icon.svg'}`" alt="AI" class="ai-empty-avatar">
                <div>{{ $t('ai.welcome') }}</div>
            </div>
        </div>

        <div class="ai-no-key" v-if="!hasKey">
            <span>{{ $t('ai.no_key') }}</span>
            <button class="btn btn-primary btn-sm" @click="openSettings">{{ $t('ai.configure') }}</button>
        </div>

        <div class="ai-input" v-else>
            <input v-model="message" type="text" :placeholder="$t('ai.ask')" @keyup.enter="send" :disabled="typing">
            <button class="btn btn-primary" @click="send" :disabled="typing || !message.length">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</template>

<script>
import { useModal } from '../../composables/useModal';
import { getProvider, hasKey, ask } from '../../lib/ai';

const HISTORY_KEY = 'ai_chat_history';

export default {
    data() {
        return {
            messages: [],
            message: '',
            typing: false
        };
    },
    computed: {
        hasKey() { return hasKey(); },
        provider() { return getProvider(); },
        providerName() { return getProvider() === 'gemini' ? 'Gemini' : 'OpenAI'; }
    },
    methods: {
        openSettings() {
            useModal().close();
            useModal().open('AiSettings', {}, 'ai.settings');
        },
        persist() {
            localStorage.setItem(HISTORY_KEY, JSON.stringify(this.messages.slice(-50)));
        },
        send() {
            if (this.typing || !this.message.length) return;
            const question = this.message;
            this.message = '';
            this.messages.push({ role: 'user', content: question });
            this.persist();
            this.typing = true;
            this.scroll();

            ask(this.messages.slice(-12)).then((r) => {
                this.messages.push({ role: 'assistant', content: r.content });
            }).catch((e) => {
                const error = e && e.message ? e.message : this.$t('ai.failed');
                this.messages.push({ role: 'assistant', content: error });
            }).finally(() => {
                this.typing = false;
                this.persist();
                this.scroll();
            });
        },
        scroll() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) el.scrollTop = el.scrollHeight;
            });
        }
    },
    mounted() {
        try {
            this.messages = JSON.parse(localStorage.getItem(HISTORY_KEY) || '[]');
        } catch (e) {
            this.messages = [];
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.ai-chat {
    display: flex;
    flex-direction: column;
    height: 58vh;
    min-height: 360px;

    .ai-chat-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-size: .85em;
        opacity: .7;
        margin-bottom: 12px;

        .ai-cog { cursor: pointer; }
    }

    .ai-messages {
        flex: 1;
        overflow-y: auto;
        display: flex;
        flex-direction: column;
        gap: 12px;
        min-height: 0;
        padding: 12px;
        border-radius: 6px;
        background: rgba(255, 255, 255, .03);
        border: 1px solid rgba(255, 255, 255, .06);

        .ai-msg {
            display: flex;
            gap: 10px;

            .ai-msg-icon {
                width: 30px;
                height: 30px;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
                font-size: .9em;
                background: rgba(255, 255, 255, .07);
            }

            .ai-chat-avatar {
                width: 18px;
                height: 18px;
                object-fit: contain;
            }

            .ai-msg-bubble {
                max-width: 80%;
                padding: 9px 13px;
                border-radius: 12px;
                font-size: .9em;
                line-height: 1.45;
                white-space: pre-wrap;
                word-break: break-word;
            }

            &.user {
                flex-direction: row-reverse;

                .ai-msg-bubble {
                    background: #e51f49;
                    color: #fff;
                    border-top-right-radius: 3px;
                }
            }

            &.assistant .ai-msg-bubble {
                background: rgba(255, 255, 255, .08);
                border-top-left-radius: 3px;
            }
        }

        .ai-typing .ai-dots {
            display: flex;
            gap: 4px;
            align-items: center;
            padding: 12px 14px;

            span {
                width: 6px;
                height: 6px;
                border-radius: 50%;
                background: rgba(255, 255, 255, .4);
                animation: ai-blink 1.2s infinite;

                &:nth-child(2) { animation-delay: .2s; }
                &:nth-child(3) { animation-delay: .4s; }
            }
        }

        .ai-empty {
            margin: auto;
            display: flex;
            flex-direction: column;
            align-items: center;
            opacity: .6;
            text-align: center;
            padding: 0 30px;

            .ai-empty-avatar {
                width: 36px;
                height: 36px;
                margin-bottom: 12px;
                opacity: 0.85;
            }

            i { font-size: 2.2em; margin-bottom: 10px; }
        }
    }

    .ai-no-key {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
        margin-top: 12px;
        font-size: .85em;
        opacity: .8;
    }

    .ai-input {
        display: flex;
        gap: 8px;
        margin-top: 12px;

        input { flex: 1; }
    }
}

@keyframes ai-blink {
    0%, 60%, 100% { opacity: .3; }
    30% { opacity: 1; }
}
</style>
