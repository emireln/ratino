<template>
    <header>
        <div class="fixed">
            <div class="container-fluid">
                <div class="header-container">
                    <router-link to="/" class="logo" title="RATINO">
                        <img src="/img/misc/logo.png" alt="RATINO">
                    </router-link>

                    <!-- Center Vault & Chips Button -->
                    <div class="header-center" v-if="store.user">
                        <div class="wallet clickable-wallet" v-if="store.currencies[store.currency]" @click="open('Wallet')" title="Open Vault & Weekly Drops">
                            <i class="fas fa-wallet wallet-icon mr-1"></i>
                            <unit :to="store.currency" :value="store.currencies[store.currency].balance.real"></unit>
                            <span class="wallet-currency">{{ store.currencies[store.currency].name }}</span>
                            <span class="wallet-add-badge"><i class="fas fa-plus"></i></span>
                        </div>
                    </div>

                    <div v-if="!store.user" class="right ml-auto">
                        <button class="btn btn-login" @click="openAuth('login')">{{ $t('general.auth.login') }}</button>
                        <button class="btn btn-register" @click="openAuth('register')">{{ $t('general.auth.register') }}</button>
                    </div>
                    <div v-else class="right">
                        <div class="action feature-buttons">
                            <i class="fas fa-users" title="Rooms / Play with Friends" @click="open('Room')"></i>
                            <i class="fas fa-user-friends" title="Friends & Social" @click="open('Friends')"></i>
                            <i class="fas fa-chart-line" :title="$t('session.stats')" @click="open('SessionStats')"></i>
                            <i class="fas fa-crown" :title="$t('vip.title')" @click="open('Vip')"></i>
                            <i class="fas fa-gift" :title="$t('heist.title')" @click="open('Heist')"></i>
                            <i class="fas fa-comments" :title="$t('chat.title')" @click="open('Chat')"></i>
                            <i class="fas fa-trophy" :title="$t('leaderboard.title')" @click="open('Leaderboard')"></i>
                            <i class="fas fa-robot" :title="$t('ai.title')" @click="open('AiChat')"></i>
                        </div>
                        <div class="account-profile-trigger" @click="open('Profile')" title="Edit Profile & Avatar">
                            <img :src="store.user.avatar || '/img/misc/incognito-default.png'" class="header-avatar" alt="">
                            <span class="account-name">{{ store.user.name }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
</template>

<script>
import { useAppStore } from '../stores/app';
import { useModal } from '../composables/useModal';
import client, { whisper } from '../lib/whisper';
import bus from '../lib/bus';

export default {
    setup() {
        return { store: useAppStore() };
    },
    methods: {
        open(name) {
            useModal().open(name);
        },
        openAuth(type) {
            useModal().open('Auth', { type });
        },
        logout() {
            client.post('/api/auth/logout').finally(() => {
                this.store.logout();
                this.$router.push('/');
            });
        },
        onSocialNotification(e) {
            if (!e) return;
            if (e.type === 'room_invite') {
                this.$toast.info(`${e.data.inviter_name} invited you to room ${e.data.room_code}!`, {
                    timeout: 8000
                });
            } else if (e.type === 'friend_request') {
                this.$toast.info(`${e.data.from_name} sent you a friend request!`);
            } else if (e.type === 'friend_accepted') {
                this.$toast.success(`${e.data.friend_name} accepted your friend request!`);
            }
        }
    },
    mounted() {
        bus.on('event:socialNotification', this.onSocialNotification);
    },
    beforeUnmount() {
        bus.off('event:socialNotification', this.onSocialNotification);
    }
};
</script>

<style lang="scss">
@import '../sass/variables';

header {
    position: sticky;
    top: 0;
    z-index: 3000;
    height: $header-height;

    .fixed {
        background: rgba(23, 24, 29, 0.92);
        backdrop-filter: blur(20px);
        border-bottom: none;
    }

    .header-container {
        display: flex;
        align-items: center;
        height: $header-height;
        padding: 0 8px;

        .logo {
            height: 48px;
            display: flex;
            align-items: center;
            text-decoration: none;
            transition: transform 0.2s ease, opacity 0.2s ease;

            &:hover {
                transform: scale(1.03);
                opacity: 0.95;
            }

            img {
                height: 38px;
                width: auto;
                max-width: 140px;
                object-fit: contain;
                display: block;
            }
        }

        .header-center {
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto;

            .wallet {
                display: flex;
                align-items: center;
                gap: 6px;
                font-weight: 700;
                background: rgba(255, 255, 255, 0.06);
                padding: 6px 16px;
                border-radius: 20px;
                border: 1px solid rgba(255, 255, 255, 0.08);
                cursor: pointer;
                transition: all 0.2s ease;

                &:hover {
                    background: rgba(229, 31, 73, 0.15);
                    border-color: rgba(229, 31, 73, 0.4);
                    transform: translateY(-1px);
                }

                .wallet-icon {
                    color: #e51f49;
                    font-size: 0.9em;
                }

                .wallet-currency {
                    font-size: .75em;
                    opacity: .65;
                    font-weight: 600;
                }

                .wallet-add-badge {
                    margin-left: 4px;
                    width: 18px;
                    height: 18px;
                    border-radius: 50%;
                    background: #e51f49;
                    color: #fff;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    font-size: 0.65em;
                }
            }
        }

        .right {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-left: auto;

            .feature-buttons i {
                margin: 0 6px;
                font-size: 1.15em;
                opacity: .7;
                cursor: pointer;
                padding: 8px;
                border-radius: 50%;
                transition: opacity .2s ease, background .2s ease, color .2s ease;

                &:hover {
                    opacity: 1;
                    color: #e51f49;
                    background: rgba(229, 31, 73, 0.12);
                }
            }

            .account-profile-trigger {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 4px 10px;
                border-radius: 20px;
                background: rgba(255, 255, 255, 0.05);
                border: 1px solid rgba(255, 255, 255, 0.08);
                cursor: pointer;
                transition: all 0.2s ease;
                margin-right: 6px;

                &:hover {
                    background: rgba(229, 31, 73, 0.15);
                    border-color: rgba(229, 31, 73, 0.4);
                }

                .header-avatar {
                    width: 26px;
                    height: 26px;
                    border-radius: 50%;
                    object-fit: cover;
                    border: 1px solid rgba(255, 255, 255, 0.2);
                }

                .account-name {
                    font-weight: 700;
                    color: #fff;
                    font-size: 0.9em;
                }
            }

            .btn {
                padding: 9px 20px;
                border-radius: 200px;
                font-size: 0.92rem;
                font-weight: 700;
                letter-spacing: 0.02em;
                cursor: pointer;
                transition: all 0.25s ease;
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .btn-login {
                background: rgba(255, 255, 255, 0.1);
                color: #ffffff !important;
                border: 1px solid rgba(255, 255, 255, 0.22);

                &:hover {
                    background: rgba(255, 255, 255, 0.18);
                    border-color: rgba(255, 255, 255, 0.45);
                    transform: translateY(-1px);
                }
            }

            .btn-register {
                background: #e51f49 !important;
                color: #ffffff !important;
                border: none !important;
                box-shadow: none !important;

                &:hover {
                    background: #ff2a55 !important;
                    box-shadow: none !important;
                    transform: none;
                }
            }

            .btn-signout {
                background: transparent;
                color: rgba(255, 255, 255, 0.7);
                border: 1px solid rgba(255, 255, 255, 0.2);

                &:hover {
                    color: #fff;
                    border-color: rgba(255, 255, 255, 0.4);
                }
            }
        }
    }
}

@media(max-width: 991px) {
    header .account-name { display: none; }
}
</style>
