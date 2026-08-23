<template>
    <transition name="modal">
        <div class="modals" v-show="state.modals.length" @click.self="close()" @keyup.esc="close()" tabindex="0">
            <div v-for="(modal, index) in state.modals" :key="index" class="xmodal" @click.stop>
                <button class="modal-close" @click="close()" aria-label="Close modal">
                    <i class="fal fa-times"></i>
                </button>
                <div class="heading" v-if="modal.title">
                    <span class="title">{{ $t(modal.title) }}</span>
                </div>
                <div class="modal_template">
                    <component :is="resolve(modal.name)" v-bind="modal.props"></component>
                </div>
            </div>
        </div>
    </transition>
</template>

<script>
import { useModal } from '../../composables/useModal';
import AuthModal from './AuthModal.vue';
import VipModal from './VipModal.vue';
import HeistModal from './HeistModal.vue';
import ChatModal from './ChatModal.vue';
import LeaderboardModal from './LeaderboardModal.vue';
import BountiesModal from './BountiesModal.vue';
import SessionStatsModal from './SessionStatsModal.vue';
import AiSettingsModal from './AiSettingsModal.vue';
import AiChatModal from './AiChatModal.vue';
import WalletModal from './WalletModal.vue';
import RoomModal from './RoomModal.vue';

const registry = {
    Auth: AuthModal,
    Vip: VipModal,
    Heist: HeistModal,
    Chat: ChatModal,
    Leaderboard: LeaderboardModal,
    Bounties: BountiesModal,
    SessionStats: SessionStatsModal,
    AiSettings: AiSettingsModal,
    AiChat: AiChatModal,
    Wallet: WalletModal,
    Room: RoomModal
};

export default {
    setup() {
        const modal = useModal();
        return { state: modal.state };
    },
    methods: {
        resolve(name) {
            return registry[name] || null;
        },
        close() {
            useModal().close();
        }
    }
};
</script>

<style lang="scss">
@import '../../sass/variables';

body.show { overflow: hidden; }

.modals {
    background: rgba(10, 11, 15, 0.75);
    backdrop-filter: blur(8px);
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    z-index: 50000;
    overflow-y: auto;
    display: flex;
    padding: 40px 20px;
}

.xmodal {
    background: #1a1b22;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 24px 60px rgba(0, 0, 0, 0.65), 0 0 1px rgba(255, 255, 255, 0.2);
    width: 500px;
    max-width: 100%;
    max-height: 85vh;
    border-radius: 16px;
    margin: auto;
    display: flex;
    flex-direction: column;
    overflow: hidden;
    position: relative;

    .modal-close {
        position: absolute;
        right: 16px;
        top: 16px;
        color: rgba(255, 255, 255, 0.6);
        cursor: pointer;
        z-index: 70;
        font-size: 1.15em;
        background: rgba(255, 255, 255, 0.08);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 50%;
        width: 34px;
        height: 34px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        padding: 0;

        &:hover {
            color: #fff;
            background: rgba(229, 31, 73, 0.85);
            border-color: #e51f49;
            transform: scale(1.05);
        }
    }

    .heading {
        text-align: center;
        font-size: 1.25em;
        font-weight: 700;
        padding: 22px 24px 0;
        color: #fff;
    }

    .modal_template {
        overflow-y: auto;
        flex: 1;
        padding: 24px 28px 28px;
    }

    input, select {
        border: 1px solid rgba(255, 255, 255, 0.12);
        background: #24252e;
        color: #fff;
        padding: 11px 16px;
        border-radius: 8px;
        width: 100%;
        font-size: 0.95em;
        transition: border-color 0.2s ease, box-shadow 0.2s ease;

        &:focus {
            border-color: #e51f49;
            box-shadow: 0 0 0 3px rgba(229, 31, 73, 0.25);
            outline: none;
        }
    }
}

.modal-enter-active, .modal-leave-active { transition: opacity .25s ease, transform .25s ease; }
.modal-enter-from, .modal-leave-to { opacity: 0; transform: scale(0.96); }
</style>
