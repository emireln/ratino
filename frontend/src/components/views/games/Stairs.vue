<template>
    <div>
        <div class="stairsColumns">
            <div class="stairsMultipliers">
                <div v-if="gameInstance.game && gameInstance.game.data && gameInstance.game.data[mines]" v-for="m in gameInstance.game.data[mines]" class="multiplier">{{ abbreviate(m) }}</div>
            </div>
            <div class="stairsContainer">
                <div class="stairsRow" v-for="(row, rowId) in rowsData" :data-row-id="rows.length - 1 - rowId">
                    <div :data-cell-id="cell.type === 0 ? null : cell.id" v-for="cell in row" :class="`stairsCell ${cell.type === 0 ? 'stairsInvisible' : 'stairsVisible'}`" @click="cellClick(rows.length - 1 - rowId, $event.target)"></div>
                </div>
                <div class="character stand"></div>
            </div>
        </div>
        <div class="d-none">
            <svg data-icon-clone class="svg-inline--fa fa-stairs fa-w-16 transformed" role="img" xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 32 32"><path fill="currentColor" d="M13.75 1.731v1.744l-0.512 0.106c-0.287 0.063-0.769 0.188-1.081 0.288l-0.563 0.181-0.281-0.269c-0.406-0.388-0.994-0.75-1.55-0.938-0.594-0.206-1.669-0.281-2.281-0.156-0.444 0.094-1.294 0.425-1.294 0.506 0 0.031 0.263 0.512 0.581 1.069 0.319 0.563 0.7 1.231 0.838 1.487l0.263 0.469-0.419 0.375c-0.231 0.206-0.6 0.575-0.825 0.825-0.256 0.281-0.444 0.431-0.5 0.412-0.313-0.1-1.163-0.188-1.563-0.162-0.913 0.063-1.969 0.5-2.6 1.069-0.319 0.294-0.906 1.019-0.869 1.075 0.019 0.025 0.331 0.225 0.7 0.444 0.375 0.213 1.025 0.6 1.456 0.863 0.431 0.256 0.787 0.469 0.794 0.475 0.013 0.006-0.037 0.15-0.1 0.325-0.125 0.338-0.425 1.512-0.456 1.769-0.019 0.131-0.081 0.175-0.431 0.287-0.744 0.238-1.175 0.506-1.775 1.119-0.425 0.425-0.619 0.681-0.794 1.031-0.263 0.525-0.487 1.319-0.487 1.731v0.269l0.769 0.006c1.3 0.006 2.638 0.056 2.675 0.106 0.019 0.025 0.087 0.281 0.144 0.569 0.063 0.294 0.194 0.775 0.294 1.081l0.181 0.55-0.25 0.256c-1.069 1.1-1.462 2.869-0.969 4.338 0.087 0.275 0.212 0.575 0.269 0.669l0.106 0.169 0.938-0.538c0.519-0.294 1.138-0.65 1.381-0.8 0.662-0.4 0.656-0.4 1.006-0.019 0.412 0.456 0.619 0.663 1.006 1l0.338 0.3-0.1 0.431c-0.112 0.5-0.125 1.281-0.031 1.694 0.037 0.156 0.081 0.35 0.094 0.438 0.050 0.262 0.45 1.050 0.706 1.394 0.281 0.375 0.613 0.7 1 0.975 0.331 0.238 0.294 0.269 0.8-0.619 0.45-0.788 1.213-2.081 1.244-2.119 0.012-0.012 0.381 0.075 0.819 0.2 0.444 0.125 0.912 0.244 1.050 0.269 0.369 0.063 0.45 0.125 0.494 0.363 0.063 0.337 0.494 1.15 0.831 1.569 0.694 0.863 1.85 1.481 2.925 1.575l0.469 0.038 0.012-1.738 0.019-1.738 0.625-0.137c0.344-0.081 0.844-0.219 1.119-0.306l0.488-0.169 0.262 0.244c0.531 0.494 1.306 0.9 2.038 1.081 0.488 0.119 1.569 0.1 2.050-0.031 0.45-0.119 1.075-0.381 1.075-0.45 0-0.025-0.175-0.35-0.387-0.725-0.219-0.375-0.431-0.75-0.475-0.837-0.044-0.081-0.25-0.444-0.45-0.794l-0.369-0.637 0.712-0.719c0.394-0.394 0.762-0.781 0.813-0.85 0.081-0.125 0.106-0.131 0.4-0.050 1.394 0.356 2.9-0.025 4-1.019 0.419-0.375 0.869-0.994 0.781-1.075-0.031-0.025-0.675-0.413-1.431-0.85s-1.394-0.813-1.425-0.837c-0.025-0.025 0.050-0.394 0.169-0.825 0.119-0.425 0.25-0.938 0.288-1.131 0.063-0.331 0.081-0.356 0.269-0.394 0.35-0.069 1.313-0.575 1.644-0.856 0.456-0.387 0.837-0.863 1.106-1.381 0.256-0.494 0.481-1.287 0.481-1.713v-0.269h-0.519c-0.288 0-1.069-0.019-1.738-0.044l-1.219-0.037-0.106-0.506c-0.056-0.281-0.194-0.781-0.3-1.113l-0.194-0.613 0.344-0.375c0.394-0.425 0.8-1.188 0.962-1.813 0.137-0.544 0.137-1.588-0.006-2.119-0.106-0.381-0.313-0.894-0.425-1.044-0.038-0.050-0.5 0.188-1.506 0.763-0.794 0.456-1.481 0.838-1.519 0.838s-0.275-0.219-0.531-0.487c-0.256-0.263-0.625-0.625-0.825-0.8-0.337-0.3-0.356-0.331-0.294-0.494 0.113-0.287 0.169-1.156 0.119-1.669-0.131-1.181-0.825-2.369-1.781-3.025-0.294-0.2-0.35-0.225-0.425-0.144-0.044 0.050-0.356 0.563-0.694 1.15-0.925 1.619-0.988 1.719-1.069 1.719-0.038 0-0.175-0.044-0.3-0.094s-0.225-0.081-0.225-0.069c0 0.019-0.1-0.013-0.219-0.063s-0.238-0.087-0.262-0.087c-0.031 0-0.15-0.031-0.269-0.063-0.119-0.025-0.381-0.087-0.575-0.125l-0.35-0.069-0.113-0.356c-0.5-1.65-2.081-2.931-3.756-3.056l-0.456-0.037v1.75zM17.125 8.35c1 0.15 1.719 0.394 2.594 0.869 0.675 0.369 1.081 0.681 1.738 1.331 1.506 1.481 2.306 3.463 2.262 5.575-0.019 1.181-0.212 2.063-0.656 3.044-0.762 1.675-2.169 3.094-3.844 3.869-2.387 1.106-5.206 0.894-7.431-0.563-2.269-1.475-3.575-3.962-3.513-6.662 0.063-2.344 1.144-4.5 2.975-5.906 0.225-0.175 0.419-0.331 0.425-0.363 0.012-0.025 0.044-0.044 0.075-0.044 0.025 0 0.244-0.113 0.475-0.25 1.394-0.819 3.25-1.156 4.9-0.9z
                                        M15.313 10.131c-0.588 0.069-1.4 0.319-1.969 0.606-2.869 1.456-4.063 4.95-2.656 7.794 0.481 0.981 1.006 1.613 1.813 2.219 2.063 1.531 4.925 1.531 7-0.006 0.438-0.319 1.025-0.9 1.225-1.212 0.063-0.106 0.156-0.225 0.2-0.275 0.15-0.156 0.556-1.031 0.719-1.538 0.087-0.275 0.175-0.688 0.194-0.906 0.025-0.225 0.056-0.481 0.075-0.575 0.050-0.256-0.094-1.387-0.238-1.85-0.525-1.713-1.619-2.987-3.181-3.725-0.656-0.306-1.113-0.438-1.875-0.537-0.575-0.069-0.637-0.069-1.306 0.006zM17.219 12.844c1.3 0.525 2.069 1.594 2.137 2.969 0.056 1.063-0.225 1.806-0.981 2.569-0.738 0.744-1.5 1.031-2.563 0.975-1.831-0.094-3.188-1.531-3.188-3.381 0-1.456 0.906-2.7 2.325-3.194 0.4-0.144 0.537-0.156 1.144-0.137 0.581 0.019 0.756 0.044 1.125 0.2z
                                        M15.481 14.525c-0.344 0.131-0.644 0.381-0.838 0.713-0.15 0.25-0.175 0.363-0.175 0.769 0 0.419 0.025 0.506 0.188 0.781 0.619 0.994 2.037 1.025 2.656 0.050 0.631-1-0.025-2.3-1.2-2.375-0.244-0.019-0.481 0.006-0.631 0.063z"></path></svg>
            <svg data-ladder-clone class="svg-inline--fa fa-ladder fa-w-16 transformed" role="img" xmlns="http://www.w3.org/2000/svg" focusable="false" viewBox="0 0 32 32"><path fill="currentColor" d="M20.372 0v2.947h-8.745v-2.947h-1.901v32h1.901v-2.947h8.745v2.947h1.901v-32h-1.901zM20.372 27.152h-8.745v-2.94h8.745v2.94zM20.372 22.311h-8.745v-2.94h8.745v2.94zM20.372 17.47h-8.745v-2.94h8.745v2.94zM20.372 12.629h-8.745v-2.94h8.745v2.94zM20.372 7.788h-8.745v-2.94h8.745v2.94z"></path></svg>
        </div>
    </div>
</template>

<script>
    export default {
        data() {
            return {
                mines: 1,

                rows: [
                    [1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
                    [0, 0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    [0, 0, 0, 0, 0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    [0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0, 0, 0],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
                    [0, 0, 0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    [0, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 0],
                    [1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1, 1]
                ]
            }
        },
        computed: {
            rowsData() {
                return this.rows.map((row) => {
                    let cell = 0;
                    return row.map((type) => ({ type: type, id: type === 0 ? null : cell++ }));
                });
            }
        },
        methods: {
            getClientData() {
                return {
                    mines: this.mines
                }
            },
            callback(response) {
                if(this.isExtendedGameStarted()) {
                    $('[data-cell-id]').removeClass('selected').removeClass('active').find('svg').remove();
                    $('.character').fadeOut('fast', () => $('.character').attr('data-flip', 'false').attr('style', '').attr('class', 'character stand').fadeIn('fast', () => this.setRow(0)));
                } else {
                    $('[data-cell-id]').removeClass('active');
                    $('.character').attr('class', 'character victory');
                    if(response) this.resultPopup(response.game);
                }
            },
            cellClick(row, target) {
                const cell = $(target).data('cell-id');
                const e = $(`[data-row-id="${row}"] [data-cell-id="${cell}"]`);

                if(!e.hasClass('active')) return;
                this.setRow(row, false);
                this.sendTurn({ cell: cell }, (response) => {
                    if(response.type === 'fail') {
                        this.setRow(row, true);
                        return;
                    }

                    $('.character').attr('class', 'character run').animate({ left: e.position().left }, e.position().left === $('.character').position().left ? 0 : 1000, () => {
                        setTimeout(() => {
                            _.forEach(response.data.death, (deathCell) => this.dropStone(row, deathCell));

                            $('.character').attr('class', 'character climb').animate({top: $(`[data-row-id="${row}"] [data-cell-id="${cell}"]`).position().top - $('.character').height()}, 800, () => {
                                if (response.type === 'finish') {
                                    this.finishExtended(false);
                                    $('.character').attr('class', 'character victory');
                                    this.playSound('/sounds/win.mp3');
                                    this.resultPopup(response.game);
                                }

                                if (response.type === 'continue') {
                                    this.setRow(row + 1, true);
                                    $('.character').attr('class', 'character stand');
                                    this.playSound('/sounds/guessed.mp3');
                                }
                                if (response.type === 'lose') {
                                    this.finishExtended(false);
                                    $('.character').attr('class', 'character death');
                                    this.playSound('/sounds/lose.mp3');
                                    this.resultPopup(response.game);
                                }
                            });
                        }, 200);
                    });

                    e.addClass('selected');
                    e.append($('[data-ladder-clone]').clone().attr('data-ladder-clone', null));
                });
            },
            gameDataRetrieved() {
                $(`[data-cell-id]`).on('mouseover', function() {
                    if(!$(this).hasClass('active')) return;
                    $('.character').attr('data-flip', $(this).position().left < $('.character').position().left);
                });
            },
            restore(game) {
                for(let i = 0; i < game.history.length; i++) $(`[data-row-id="${i}"] [data-cell-id="${game.history[i]}"]`).addClass('selected');
                this.setRow(game.history.length);
                if(game.history.length > 0) {
                    const e = $(`[data-row-id="${game.history.length - 1}"] [data-cell-id="${game.history[game.history.length - 1]}"]`);
                    $('.character').css({ top: e.position().top - $('.character').height(), left: e.position().left });
                }
            },
            dropStone(row, cell) {
                const transformId = `_${Math.random()}`;
                const e = $('[data-icon-clone]').clone().attr('data-transform-id', transformId).attr('data-icon-clone', null);
                $(`[data-row-id="${row}"] [data-cell-id="${cell}"]`).append(e);
                e.hide().css({ top: -40 });
                setTimeout(() => $(`[data-transform-id="${transformId}"]`).show().animate({top: -14}, 700), 300);
            },
            setRow(id, active = true) {
                $(`[data-row-id="${id}"] .stairsCell`).toggleClass('active', active);
            },
            getSidebarComponents() {
                return [
                    { name: 'label', data: { label: this.$t('general.wager') } },
                    { name: 'wager-classic' },
                    { name: 'label', data: { label: this.$t('general.mines') } },
                    { name: 'buttons', data: { buttons: [
                        { label: 1, callback: () => this.mines = 1 },
                        { label: 2, callback: () => this.mines = 2 },
                        { label: 3, callback: () => this.mines = 3 },
                        { label: 4, callback: () => this.mines = 4 },
                        { label: 5, callback: () => this.mines = 5 },
                        { label: 6, callback: () => this.mines = 6 },
                        { label: 7, callback: () => this.mines = 7 }
                    ] } },
                    { name: 'play' },
                    { name: 'footer', data: { buttons: ['help', 'sound', 'stats'] } }
                ];
            }
        }
    }
</script>

<style lang="scss">
    @import "../../../sass/variables";

    .game-stairs {
        .game-content-stairs {
            display: flex;
            align-items: center;
            justify-content: center;
        }

        @keyframes character-climb {
            from { background-position-x: 0; }
            to { background-position-x: -253px; }
        }

        @keyframes character-stand {
            from { background-position-x: 0; }
            to { background-position-x: -236px; }
        }

        @keyframes character-victory {
            from { background-position-x: 0; }
            to { background-position-x: -253px; }
        }

        @keyframes character-death {
            from { background-position-x: 0; }
            to { background-position-x: -(352px - (352 / 10)); }
        }

        @keyframes character-run {
            from { background-position-x: 0; }
            to { background-position-x: -295px; }
        }

        .stairsColumns {
            @include themed() {
                display: flex;
                flex-direction: row;

                .stairsMultipliers {
                    display: flex;
                    flex-direction: column-reverse;
                    margin-right: 15px;

                    .multiplier {
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        height: 33%;
                        text-align: center;
                        color: rgba(t('text'), 0.45);
                        cursor: default;
                        user-select: none;
                    }

                    .multiplier.active {
                        color: t('text');
                    }
                }

                .stairsContainer {
                    display: flex;
                    flex-direction: column;
                    position: relative;

                    .character {
                        pointer-events: none;
                        height: 36px;
                        width: 24px;
                        position: absolute;
                        bottom: -27px;
                    }

                    [data-flip="true"] {
                        transform: scaleX(-1);
                    }

                    .character.stand {
                        background-image: url('/img/stairs/stand.png');
                        animation: character-stand 0.8s steps(10);
                        animation-iteration-count: infinite;
                    }

                    .character.run {
                        background-image: url('/img/stairs/run.png');
                        animation: character-run 0.8s steps(10);
                        animation-iteration-count: infinite;
                    }

                    .character.victory {
                        background-image: url('/img/stairs/victory.png');
                        animation: character-victory 0.8s steps(10);
                        animation-iteration-count: infinite;
                    }

                    .character.death {
                        background-image: url('/img/stairs/death.png');
                        animation: character-death 0.8s steps(9);
                        animation-fill-mode: forwards;
                        animation-iteration-count: 1;
                        width: 30px;
                    }

                    .character.climb {
                        background-image: url('/img/stairs/climb.png');
                        animation: character-climb 0.8s steps(10);
                        animation-iteration-count: infinite;
                    }

                    .stairsRow {
                        display: flex;
                        flex-direction: row;
                        margin: 11px 0;

                        .stairsCell {
                            display: inline-flex;
                            width: 100%;
                            padding: 10px 20px;
                            margin-right: 4px;
                            border-radius: 3px;
                            transition: background 0.3s ease;
                            position: relative;

                            svg {
                                width: 85%;
                                position: absolute;
                                left: 50%;
                                transform: translateX(-50%);
                            }

                            .fa-ladder {
                                color: t('secondary');
                                font-size: 3em;
                                top: -4px;
                            }
                        }

                        .stairsCell.stairsVisible {
                            background: t('secondary');
                            cursor: default;
                        }

                        .stairsCell.stairsVisible.active {
                            cursor: pointer;
                        }

                        .stairsCell.stairsVisible.active,
                        .stairsCell.stairsVisible.selected {
                            background: t('text');
                        }

                        .stairsCell.stairsInvisible {
                            background: transparent;
                            pointer-events: none;
                        }

                    }

                }
            }
        }

        @media (max-width: 1650px) {
            .stairsColumns {
                @include themed() {
                    .stairsMultipliers {
                        .multiplier {
                            height: 22.6%;
                        }
                    }

                    .stairsContainer {
                        .stairsRow {
                            margin: 11px 0;

                            .stairsCell {
                                padding: 7px 13px;
                            }
                        }
                    }
                }
            }
        }

        @media (max-width: 1400px) {
            .stairsColumns {
                .character {
                    transform: scale(0.5) !important;
                    margin-top: 9px;
                }

                [data-flip="true"] {
                    transform: scale(0.5) scaleX(-1) !important;
                }

                .fa-ladder {
                    top: -16px !important;
                }

                @include themed() {
                    .stairsMultipliers {
                        .multiplier {
                            height: 30.5%;
                            font-size: 0.805em;
                        }
                    }

                    .stairsContainer {
                        .stairsRow {
                            margin: 11px 0;

                            .stairsCell {
                                padding: 5px 9px;
                            }
                        }
                    }
                }
            }
        }

        @media (max-width: 1100px) {
            .stairsColumns {
                @include themed() {
                    .stairsMultipliers {
                        .multiplier {
                            height: 34.8%;
                            font-size: 0.7em;
                        }
                    }

                    .stairsContainer {
                        .stairsRow {
                            margin: 11px 0;

                            .stairsCell {
                                padding: 4px 6px;
                                border-radius: 1px;
                            }
                        }
                    }
                }
            }
        }

        @media (max-width: 485px) {
            .stairsColumns {
                @include themed() {
                    .stairsMultipliers {
                        .multiplier {
                            height: 43.9%;
                            font-size: 0.6em;
                        }
                    }

                    .stairsContainer {
                        .stairsRow {
                            margin: 11px 0;

                            .stairsCell {
                                padding: 3px 5px;
                                border-radius: 1px;
                            }
                        }
                    }
                }
            }
        }
    }

    .overview-render-target {
        .stairsColumns {
            .stairsContainer {
                .stairsRow {
                    margin: 5px 0 !important;

                    .stairsCell {
                        padding: 4px 7px !important;
                        border-radius: 1px !important;
                    }
                }
            }
        }
    }
</style>
