<template>
    <div class="sidebar-play">
        <div class="play-button" @click="play">
            {{ isExtendedGameStarted() ? (gameInstance.game.canBeFinished !== false ? $t('general.take') : $t('general.play')) : $t('general.play') }}
        </div>
    </div>
</template>

<script>
import { whisper } from '../../../lib/whisper';
import { useAppStore } from '../../../stores/app';

export default {
    computed: {
        gameInstance() { return useAppStore().gameInstance; }
    },
    methods: {
        play() {
            const ref = this.$parent && this.$parent.$parent && this.$parent.$parent.$refs
                ? (this.$parent.$parent.$refs.gameComponent || window.$gameRef)
                : window.$gameRef;
            if (!ref) return;

            if (this.isExtendedGameStarted()) {
                if (ref.extendedAutoBetHandle && this.gameInstance.bettingType !== 'manual') {
                    ref.extendedAutoBetHandle(() => this.finishExtended(true));
                } else {
                    this.finishExtended(true);
                }
                return;
            }

            const request = {
                api_id: this.gameInstance.game.id,
                bet: this.gameInstance.bet,
                currency: useAppStore().currency,
                demo: useAppStore().demo,
                quick: useAppStore().quick,
                data: ref.getClientData ? ref.getClientData() : {}
            };

            whisper('Play', request).then((response) => {
                ref.callback(response.response);
                if (response.response && response.response.id) {
                    this.updateGameInstance((i) => {
                        i.game.extendedId = response.response.id;
                        i.game.extendedState = 'in-progress';
                        i.bet = response.response.wager;
                    });
                }
            }).catch((code) => {
                if (code === -4) this.$toast.error('Not enough money');
                else if (code === -2) this.$toast.error('Login to play');
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.play-button {
    margin-top: 12px;
    padding: 16px;
    border-radius: 6px;
    background: #e51f49;
    color: #fff;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    font-size: 1.05em;
    user-select: none;
    transition: all .3s ease;

    &:hover { filter: brightness(1.1); }

    &.disabled {
        opacity: .5;
        cursor: not-allowed;
    }
}
</style>
