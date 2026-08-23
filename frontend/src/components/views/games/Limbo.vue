<template>
    <div>
        <div class="limbo_canvas">
            <img src="/img/misc/mountain.png" alt class="limbo-bg">
            <div :class="`bg-star show_${star}`">
                <div class="l-star e-r"></div>
                <div class="l-star s-p"></div>
                <div class="l-star r-p"></div>
            </div>
            <div class="game-rocket notranslate">
                <div class="rocket-number">
                    <span class="rocket_payout">x{{ payout.toFixed(2) }}</span>
                    <div class="rocket-boom"></div>
                </div>
                <div class="rocket-wrap fire">
                    <div class="rocket-img">
                        <img src="/img/misc/rocket.png" alt>
                    </div>
                    <div class="rocket-fire"></div>
                </div>
            </div>
        </div>
        <div class="limbo-footer">
            <div class="row">
                <div class="col-6 pr-2">
                    <div>{{ $t('general.target_payout') }}</div>
                    <input type="number" v-model="target_payout" step=".01" :placeholder="$t('general.target_payout')">
                </div>
                <div class="col-6 pl-2">
                    <div>{{ $t('general.win_chance') }}</div>
                    <input type="number" v-model="win_chance" value="50.00" step=".01" :placeholder="$t('general.win_chance')">
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import bus from '@/lib/bus';

    export default {
        data() {
            return {
                target_payout: (2).toFixed(8),
                win_chance: (50).toFixed(8),

                star: 0,
                payout: 1
            }
        },
        created() {
            setInterval(() => {
                this.star++;
                if(this.star >= 3) this.star = 0;
            }, 10000);
        },
        watch: {
            target_payout(value, oldValue) {
                if(isNaN(value) || value < 1.01 || value > 1000000) return this.target_payout = oldValue;
                this.win_chance = parseFloat(this.calculate(value).toFixed(8));
            },
            win_chance(value, oldValue) {
                if(isNaN(value) || value < 0.000099 || value > 98) return this.win_chance = oldValue;
                this.target_payout = parseFloat(this.calculate(value).toFixed(8));
            }
        },
        methods: {
            callback(response) {
                let win = response.game.win, delay = 200;

                this.playSound('/sounds/flying.mp3');

                $('.rocket-wrap').addClass('flying');
                $('.rocket_payout').attr('class', 'rocket_payout');

                setTimeout(() => {
                    this.payout = response.game.data.number;
                    $('.rocket-wrap, .rocket-boom').addClass('boom');

                    this.playSound('/sounds/boom.mp3');

                    setTimeout(() => {
                        this.playSound(`/sounds/${win ? 'guessed' : 'lose'}.mp3`);
                        this.updateGameInstance((i) => i.playTimeout = false);

                        $('.rocket_payout').toggleClass('text-danger', !win).toggleClass('text-success', win);

                        bus.emit('limbo:history:addEntry', { html: `<div class="text-${win ? 'success' : 'danger'}">${response.server_seed.result[0].toFixed(2)}</div>` });

                        $('.rocket-wrap').removeClass('flying').removeClass('boom');
                        $('.rocket-boom').removeClass('boom');
                    }, delay * 2);
                }, delay);
            },
            calculate(value) {
                return (1000000 / value) / 10000;
            },
            getClientData() {
                return {
                    target_payout: this.target_payout
                }
            },
            getSidebarComponents() {
                return [
                    { name: 'label', data: { label: this.$t('general.wager') } },
                    { name: 'wager-classic' },
                    { name: 'auto-bets' },
                    { name: 'play' },
                    { name: 'footer', data: { buttons: ['help', 'sound', 'stats'] } },
                    { name: 'history', data: { scrollable: true } }
                ];
            }
        }
    }
</script>

<style lang="scss">
    @import "../../../sass/variables";

    .limbo_canvas {
        position: relative;
        height: 320px;
        border-radius: 8px;
        overflow: hidden;
        background: linear-gradient(180deg, #131722 0%, #1c2333 100%);
        display: flex;
        align-items: center;
        justify-content: center;

        .limbo-bg {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 60%;
            object-fit: cover;
            opacity: .5;
        }

        .bg-star {
            position: absolute;
            inset: 0;
            background:
                radial-gradient(1px 1px at 20% 30%, #fff 50%, transparent 50%),
                radial-gradient(1px 1px at 70% 60%, #fff 50%, transparent 50%),
                radial-gradient(1.5px 1.5px at 40% 70%, #fff 50%, transparent 50%),
                radial-gradient(1px 1px at 85% 20%, #fff 50%, transparent 50%),
                radial-gradient(1px 1px at 55% 15%, #fff 50%, transparent 50%);
            opacity: .4;
        }

        .game-rocket {
            position: relative;
            z-index: 2;
            text-align: center;

            .rocket_payout {
                font-size: 2.2em;
                font-weight: 800;
                transition: color .3s ease;
            }

            .rocket-wrap {
                width: 90px;
                margin: 12px auto 0;
                transition: transform .5s ease;

                &.flying {
                    animation: limboFly 1.2s ease forwards;
                }

                .rocket-img img {
                    width: 90px;
                    transform: rotate(45deg);
                    filter: drop-shadow(0 8px 18px rgba(223, 52, 79, .5));
                }

                .rocket-fire {
                    width: 30px;
                    height: 46px;
                    margin: -8px auto 0;
                    background: linear-gradient(180deg, #ffd23f, #e51f49 70%, transparent);
                    clip-path: polygon(35% 0, 65% 0, 100% 100%, 0 100%);
                    animation: limboFire .3s infinite alternate;
                }

                &.boom .rocket-fire { opacity: 0; }

                .rocket-boom {
                    opacity: 0;
                }
            }
        }
    }

    @keyframes limboFly {
        0% { transform: translateY(0) scale(1); }
        100% { transform: translateY(-110px) scale(1.15); }
    }

    @keyframes limboFire {
        from { transform: scaleY(.8); opacity: .9; }
        to { transform: scaleY(1.1); opacity: 1; }
    }

    .limbo-footer {
        margin-top: 18px;

        input {
            margin-top: 6px;
        }
    }
</style>
