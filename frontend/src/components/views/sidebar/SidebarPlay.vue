<template>
    <div class="sidebar-play">
        <button type="button" class="play-button" @click="play">
            {{ isExtendedGameStarted() ? $t('general.take') : $t('general.play') }}
        </button>
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

            const wager = typeof ref.customWagerCalculation === 'function'
                ? Number(ref.customWagerCalculation())
                : Number(this.gameInstance.bet);
            if (!Number.isFinite(wager) || wager <= 0) {
                this.$toast.error('Enter a valid wager');
                return;
            }

            const request = {
                api_id: this.gameInstance.game.id,
                bet: wager,
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
                else if (code === -9 || code === -2) this.$toast.error('Login to play');
                else if (code === -8) this.$toast.error('Finish your current round first');
                else if (code === -5) this.$toast.error('Game is disabled');
                else if (code !== 0) this.$toast.error('Bet rejected, try again');
            });
        }
    }
};
</script>

<style lang="scss" scoped>
.play-button {
    width: 100%;
    margin-top: 12px;
    padding: 16px;
    border-radius: 6px;
    background: #e51f49;
    color: #fff;
    font-weight: 700;
    text-align: center;
    cursor: pointer;
    font-size: 1.05em;
    font-family: inherit;
    border: 0;
    user-select: none;
    transition: all .3s ease;

    &:hover { filter: brightness(1.1); }
    &:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }

    &.disabled {
        opacity: .5;
        cursor: not-allowed;
    }
}
</style>
