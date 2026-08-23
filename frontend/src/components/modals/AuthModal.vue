<template>
    <div class="auth-modal-content">
        <div class="auth-brand-header">
            <img src="/img/misc/logo.png" alt="RATINO" class="auth-logo">
            <p class="auth-subtitle">{{ type === 'login' ? 'Welcome back! Sign in to play.' : 'Create an account to start playing.' }}</p>
        </div>

        <div class="auth-tabs">
            <button :class="['auth-tab', type === 'login' ? 'active' : '']" @click="type = 'login'" type="button">
                {{ $t('general.auth.login') }}
            </button>
            <button :class="['auth-tab', type === 'register' ? 'active' : '']" @click="type = 'register'" type="button">
                {{ $t('general.auth.register') }}
            </button>
        </div>

        <div class="auth-form">
            <div class="form-group mb-3" v-if="type === 'register'">
                <label class="form-label">{{ $t('general.auth.credentials.email') }}</label>
                <div class="input-with-icon">
                    <i class="fas fa-envelope"></i>
                    <input v-model="email" type="email" :placeholder="$t('general.auth.credentials.email')">
                </div>
            </div>

            <div class="form-group mb-3">
                <label class="form-label">{{ $t('general.auth.credentials.login') }}</label>
                <div class="input-with-icon">
                    <i class="fas fa-user"></i>
                    <input v-model="login" type="text" :placeholder="$t('general.auth.credentials.login')">
                </div>
            </div>

            <div class="form-group mb-4">
                <label class="form-label">{{ $t('general.auth.credentials.password') }}</label>
                <div class="input-with-icon">
                    <i class="fas fa-lock"></i>
                    <input v-model="password" type="password" :placeholder="$t('general.auth.credentials.password')" @keyup.enter="done">
                </div>
            </div>

            <button class="btn btn-auth-submit" @click="done" :disabled="loading">
                <span v-if="loading"><i class="fas fa-spinner fa-spin mr-2"></i> Processing...</span>
                <span v-else>{{ $t('general.auth.' + type) }}</span>
            </button>
        </div>
    </div>
</template>

<script>
import client from '../../lib/whisper';
import { useAppStore } from '../../stores/app';
import { useModal } from '../../composables/useModal';
import { connectRealtime } from '../../lib/echo';

export default {
    props: {
        type: { type: String, default: 'login' }
    },
    data() {
        return {
            email: '',
            login: '',
            password: '',
            loading: false
        };
    },
    methods: {
        done() {
            if (this.loading) return;
            const store = useAppStore();
            const modal = useModal();
            this.loading = true;

            if (this.type === 'register') {
                client.post('/api/auth/register', {
                    email: this.email,
                    name: this.login,
                    password: this.password
                }).then(({ data }) => {
                    store.setUserData(data);
                    store.updateData();
                    connectRealtime(data.user._id);
                    modal.close();
                }).catch(() => {
                    this.$toast.error(this.$t('general.auth.wrong_credentials'));
                }).finally(() => {
                    this.loading = false;
                });
            } else {
                client.post('/api/auth/login', {
                    login: this.login,
                    password: this.password
                }).then(({ data }) => {
                    store.setUserData(data);
                    store.updateData();
                    connectRealtime(data.user._id);
                    modal.close();
                }).catch(() => {
                    this.$toast.error(this.$t('general.auth.wrong_credentials'));
                }).finally(() => {
                    this.loading = false;
                });
            }
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

.auth-modal-content {
    width: 100%;
}

.auth-brand-header {
    text-align: center;
    margin-bottom: 20px;
    padding-top: 4px;

    .auth-logo {
        height: 38px;
        width: auto;
        max-width: 150px;
        object-fit: contain;
        margin: 0 auto 8px;
        display: block;
    }

    .auth-subtitle {
        font-size: 0.88em;
        color: rgba(255, 255, 255, 0.55);
        margin: 0;
    }
}

.auth-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    background: rgba(0, 0, 0, 0.25);
    padding: 4px;
    border-radius: 10px;
    border: 1px solid rgba(255, 255, 255, 0.06);

    .auth-tab {
        flex: 1;
        text-align: center;
        padding: 10px 16px;
        border-radius: 7px;
        cursor: pointer;
        font-weight: 700;
        font-size: .88em;
        letter-spacing: 0.03em;
        text-transform: uppercase;
        background: transparent;
        border: none;
        color: rgba(255, 255, 255, 0.6);
        transition: all 0.2s ease;

        &:hover {
            color: #fff;
        }

        &.active {
            background: #e51f49;
            color: #fff;
            box-shadow: 0 4px 12px rgba(229, 31, 73, 0.35);
        }
    }
}

.auth-form {
    .form-group {
        position: relative;

        .form-label {
            display: block;
            font-size: 0.82em;
            font-weight: 600;
            color: rgba(255, 255, 255, 0.7);
            margin-bottom: 6px;
            text-transform: capitalize;
        }
    }

    .input-with-icon {
        position: relative;
        display: flex;
        align-items: center;

        i {
            position: absolute;
            left: 14px;
            color: rgba(255, 255, 255, 0.35);
            font-size: 0.95em;
            pointer-events: none;
        }

        input {
            padding-left: 40px !important;
        }
    }

    .btn-auth-submit {
        width: 100%;
        padding: 13px 20px;
        border-radius: 8px;
        font-size: 1rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        background: linear-gradient(135deg, #ff2a55 0%, #e51f49 100%) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 6px 18px rgba(229, 31, 73, 0.45);
        cursor: pointer;
        transition: all 0.25s ease;

        &:hover:not(:disabled) {
            background: linear-gradient(135deg, #ff4167 0%, #f02550 100%) !important;
            box-shadow: 0 8px 24px rgba(229, 31, 73, 0.6);
            transform: translateY(-1px);
        }

        &:disabled {
            opacity: 0.65;
            cursor: not-allowed;
        }
    }
}
</style>
