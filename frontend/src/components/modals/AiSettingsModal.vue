<template>
    <div class="ai-settings">
        <div class="ai-providers">
            <div :class="['ai-provider', provider === 'gemini' ? 'active' : '']" @click="select('gemini')">
                <i class="fas fa-sparkles"></i>
                <span>Google Gemini</span>
            </div>
            <div :class="['ai-provider', provider === 'openai' ? 'active' : '']" @click="select('openai')">
                <i class="fas fa-bolt"></i>
                <span>OpenAI</span>
            </div>
        </div>

        <div class="ai-key-row">
            <input v-model="key" type="password" :placeholder="$t('ai.key_placeholder')" @keyup.enter="save">
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
            ask([{ role: 'user', content: 'ping' }], 1).then((r) => {
                this.statusText = this.$t('ai.latency', { ms: r.ms });
                this.statusClass = 'ok';
                this.statusIcon = 'fas fa-bolt';
            }).catch(() => {
                this.statusText = this.$t('ai.failed');
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
        gap: 8px;
        padding: 14px;
        border-radius: 6px;
        cursor: pointer;
        font-weight: 600;
        background: rgba(255, 255, 255, .06);

        &.active {
            background: #e51f49;
            color: #fff;
        }
    }
}

.ai-key-row { margin-bottom: 14px; }

.ai-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 14px;

    .btn { flex: 1; }
}

.ai-status {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .9em;
    padding: 10px 12px;
    border-radius: 4px;
    margin-bottom: 14px;

    &.ok { background: rgba(47, 214, 114, .1); color: #2fd672; }
    &.err { background: rgba(223, 52, 79, .1); color: #e51f49; }
}

.ai-note {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: .78em;
    opacity: .6;
}
</style>
