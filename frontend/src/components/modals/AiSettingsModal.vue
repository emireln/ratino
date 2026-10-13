<template>
    <div class="ai-settings">
        <div class="ai-providers" role="group" :aria-label="$t('ai.provider')">
            <button v-for="p in providers" :key="p.id" type="button" :class="['ai-provider', provider === p.id ? 'active' : '']" :aria-pressed="provider === p.id" @click="select(p.id)">
                <img :src="p.icon" alt="" class="ai-provider-icon">
                <span>{{ p.name }}</span>
            </button>
        </div>

        <div class="ai-key-row">
            <input v-model="apiKey" type="password" autocomplete="off" spellcheck="false" :placeholder="$t('ai.key_placeholder')" :aria-label="$t('ai.key_placeholder')" class="ai-key-input" @keydown.enter="onEnter">
        </div>

        <div class="ai-actions">
            <button type="button" class="btn btn-primary" :disabled="!apiKey.trim()" @click="save">{{ $t('ai.save') }}</button>
            <button type="button" class="btn btn-secondary" :disabled="testing || !apiKey.trim()" @click="test">
                {{ testing ? $t('ai.testing') : $t('ai.test') }}
            </button>
        </div>

        <button v-if="savedKey()" type="button" class="ai-remove" @click="remove">{{ $t('ai.remove') }}</button>

        <div class="ai-status" role="status" :class="status ? status.kind : ''" v-if="status">
            <i :class="status.icon"></i>
            <span>{{ status.text }}</span>
        </div>

        <div class="ai-note">
            <i class="fas fa-lock"></i>
            <span>{{ $t('ai.privacy') }}</span>
        </div>
    </div>
</template>

<script>
import { PROVIDERS, getProvider, getKey, setKey, removeKey, setProvider, testKey, errorKey } from '../../lib/ai';

export default {
    data() {
        return {
            provider: getProvider(),
            apiKey: getKey(),
            testing: false,
            status: null
        };
    },
    computed: {
        providers() {
            return [
                { id: 'gemini', name: PROVIDERS.gemini.name, icon: '/img/misc/gemini-icon.svg' },
                { id: 'openai', name: PROVIDERS.openai.name, icon: '/img/misc/openai-icon.svg' }
            ];
        }
    },
    methods: {
        savedKey() {
            return getKey(this.provider).length > 0;
        },
        select(provider) {
            this.provider = provider;
            this.apiKey = getKey(provider);
            this.status = null;
        },
        onEnter(event) {
            if (!event.isComposing) this.save();
        },
        save() {
            const key = this.apiKey.trim();
            if (!key) return;
            setProvider(this.provider);
            setKey(this.provider, key);
            this.status = { kind: 'ok', icon: 'fas fa-check-circle', text: this.$t('ai.saved') };
        },
        remove() {
            removeKey(this.provider);
            this.apiKey = '';
            this.status = { kind: 'ok', icon: 'fas fa-check-circle', text: this.$t('ai.removed') };
        },
        test() {
            // Tests the key as typed; nothing is saved until the player presses Save.
            const key = this.apiKey.trim();
            if (!key || this.testing) return;
            this.testing = true;
            this.status = null;
            testKey(this.provider, key).then((r) => {
                this.status = { kind: 'ok', icon: 'fas fa-bolt', text: this.$t('ai.latency', { ms: r.ms }) };
            }).catch((e) => {
                const detail = e && e.detail ? ` (${e.detail})` : '';
                this.status = { kind: 'err', icon: 'fas fa-times-circle', text: this.$t(errorKey(e)) + detail };
            }).finally(() => {
                this.testing = false;
            });
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.ai-providers {
    display: flex;
    gap: 10px;
    margin-bottom: 16px;

    .ai-provider {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 12px 14px;
        border-radius: 8px;
        cursor: pointer;
        font: inherit;
        font-weight: 600;
        color: inherit;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s ease;

        &:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.14);
        }

        &:focus-visible {
            outline: 2px solid #e51f49;
            outline-offset: 2px;
        }

        &.active {
            background: rgba(229, 31, 73, 0.18);
            border-color: #e51f49;
            color: #ff476d;
        }

        .ai-provider-icon {
            width: 22px;
            height: 22px;
            object-fit: contain;
        }
    }
}

.ai-key-row {
    margin-bottom: 14px;

    .ai-key-input {
        width: 100%;
        padding: 10px 14px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 8px;
        color: #fff;
        font-size: 0.9em;
        outline: none;
        transition: border-color 0.2s ease;

        &:focus {
            border-color: #e51f49;
        }
    }
}

.ai-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;

    .btn {
        flex: 1;
        border-radius: 8px;
        height: 38px;
        font-weight: 700;
    }
}

.ai-remove {
    background: none;
    border: 0;
    color: #ff8a9c;
    font-size: .85em;
    padding: 0;
    margin-bottom: 14px;
    text-decoration: underline;
    cursor: pointer;
}

.ai-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .88em;
    padding: 10px 12px;
    border-radius: 8px;
    margin-bottom: 14px;
    word-break: break-word;

    &.ok { background: rgba(47, 214, 114, .12); color: #2fd672; border: 1px solid rgba(47, 214, 114, 0.25); }
    &.err { background: rgba(223, 52, 79, .12); color: #e51f49; border: 1px solid rgba(223, 52, 79, 0.25); }
}

.ai-note {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: .78em;
    opacity: .6;
    line-height: 1.4;
}
</style>
