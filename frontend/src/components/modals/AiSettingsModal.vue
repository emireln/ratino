<template>
    <div class="ai-settings">
        <div class="ai-providers">
            <div :class="['ai-provider', provider === 'gemini' ? 'active' : '']" @click="select('gemini')">
                <img src="/img/misc/gemini-icon.svg" alt="Gemini" class="ai-provider-icon">
                <span>Google Gemini</span>
            </div>
            <div :class="['ai-provider', provider === 'openai' ? 'active' : '']" @click="select('openai')">
                <img src="/img/misc/openai-icon.svg" alt="OpenAI" class="ai-provider-icon">
                <span>OpenAI</span>
            </div>
        </div>

        <div class="ai-key-row">
            <input v-model="key" type="password" :placeholder="$t('ai.key_placeholder')" @keyup.enter="save" class="ai-key-input">
        </div>

        <div class="ai-actions">
            <button class="btn btn-primary" @click="save">{{ $t('ai.save') }}</button>
            <button class="btn btn-secondary" @click="test" :disabled="testing || !key.length">
                {{ testing ? $t('ai.testing') : $t('ai.test') }}
            </button>
        </div>

        <div class="ai-status" :class="statusClass" v-if="statusText">
            <i :class="statusIcon"></i>
            <span>{{ statusText }}</span>
        </div>

        <div class="ai-note">
            <i class="fas fa-lock"></i>
            {{ $t('ai.privacy') }}
        </div>
    </div>
</template>

<script>
import { getProvider, getKey, setKey, setProvider, ask } from '../../lib/ai';

export default {
    data() {
        return {
            provider: getProvider(),
            key: getKey(),
            testing: false,
            statusText: '',
            statusClass: '',
            statusIcon: ''
        };
    },
    methods: {
        select(provider) {
            this.provider = provider;
            this.key = getKey(provider);
            this.statusText = '';
        },
        save() {
            setProvider(this.provider);
            setKey(this.provider, this.key);
            this.statusText = this.$t('ai.saved');
            this.statusClass = 'ok';
            this.statusIcon = 'fas fa-check-circle';
        },
        test() {
            this.testing = true;
            this.statusText = '';
            setProvider(this.provider);
            setKey(this.provider, this.key);
            ask([{ role: 'user', content: 'ping' }], 10).then((r) => {
                this.statusText = this.$t('ai.latency', { ms: r.ms });
                this.statusClass = 'ok';
                this.statusIcon = 'fas fa-bolt';
            }).catch((err) => {
                this.statusText = (err && err.message) ? err.message : this.$t('ai.failed');
                this.statusClass = 'err';
                this.statusIcon = 'fas fa-times-circle';
            }).finally(() => this.testing = false);
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
        font-weight: 600;
        background: rgba(255, 255, 255, .05);
        border: 1px solid rgba(255, 255, 255, 0.08);
        transition: all 0.2s ease;

        &:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.14);
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
    align-items: center;
    gap: 8px;
    font-size: .78em;
    opacity: .6;
}
</style>
